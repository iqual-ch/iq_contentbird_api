<?php

namespace Drupal\iq_contentbird_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\iq_contentbird_api\Event\ContentbirdWebhookEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

/**
 * Controller for handling incoming contentbird webhook events.
 *
 * This controller receives POST requests from contentbird when webhook events
 * are triggered (e.g., content status transitions). It validates the request,
 * dispatches a ContentbirdWebhookEvent, and returns an appropriate response.
 *
 * Configure the webhook URL in contentbird:
 * https://your-drupal-site.com/iq_contentbird_api/webhook
 */
class WebhookController extends ControllerBase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $webhookLogger;

  /**
   * Constructs a WebhookController object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    EventDispatcherInterface $event_dispatcher,
    RequestStack $request_stack,
    LoggerInterface $logger,
  ) {
    $this->eventDispatcher = $event_dispatcher;
    $this->requestStack = $request_stack;
    $this->webhookLogger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('request_stack'),
      $container->get('logger.factory')->get('iq_contentbird_api'),
    );
  }

  /**
   * Handles incoming webhook requests from contentbird.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response indicating success or failure.
   */
  public function handle(): JsonResponse {
    $request = $this->requestStack->getCurrentRequest();
    $rawData = $request->getContent();

    // Validate the webhook secret if configured.
    $config = $this->config('iq_contentbird_api.settings');
    $webhookSecret = $config->get('webhook_secret');

    if (!empty($webhookSecret)) {
      $receivedSignature = $request->headers->get('X-Webhook-Signature')
        ?? $request->headers->get('Webhook-Signature');

      if (empty($receivedSignature)) {
        $this->webhookLogger->warning('Webhook request received without signature header.');
        return new JsonResponse(['error' => 'Missing signature'], 401);
      }

      $computedSignature = base64_encode(hash_hmac('sha256', $rawData, $webhookSecret, TRUE));
      if (!hash_equals($computedSignature, $receivedSignature)) {
        $this->webhookLogger->warning('Webhook request received with invalid signature.');
        return new JsonResponse(['error' => 'Invalid signature'], 401);
      }
    }

    // Parse the JSON payload.
    $content = json_decode($rawData, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->webhookLogger->error('Webhook received invalid JSON data: @error', [
        '@error' => json_last_error_msg(),
      ]);
      return new JsonResponse(['error' => 'Invalid JSON data'], 400);
    }

    // Determine the event type from the payload.
    $eventType = $content['event'] ?? $content['type'] ?? 'unknown';

    $this->webhookLogger->info('Webhook received: type=@type', [
      '@type' => $eventType,
    ]);

    // Dispatch the webhook event so other modules can react.
    $webhookEvent = new ContentbirdWebhookEvent($content, $eventType);
    $this->eventDispatcher->dispatch($webhookEvent, ContentbirdWebhookEvent::WEBHOOK_RECEIVED);

    return new JsonResponse(['status' => 'success'], 200);
  }

}
