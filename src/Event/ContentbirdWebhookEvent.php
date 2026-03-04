<?php

namespace Drupal\iq_contentbird_api\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a contentbird webhook is received.
 *
 * Other modules can subscribe to this event to react to contentbird webhook
 * events such as content status transitions, content updates, etc.
 *
 * Example subscriber:
 * @code
 * public static function getSubscribedEvents() {
 *   return [
 *     ContentbirdWebhookEvent::WEBHOOK_RECEIVED => 'onWebhookReceived',
 *   ];
 * }
 *
 * public function onWebhookReceived(ContentbirdWebhookEvent $event) {
 *   $data = $event->getData();
 *   $eventType = $event->getEventType();
 *   // Process the webhook data...
 * }
 * @endcode
 */
class ContentbirdWebhookEvent extends Event {

  /**
   * Event name for webhook received.
   */
  const WEBHOOK_RECEIVED = 'iq_contentbird_api.webhook_received';

  /**
   * The webhook payload data.
   *
   * @var array
   */
  protected array $data;

  /**
   * The webhook event type.
   *
   * @var string
   */
  protected string $eventType;

  /**
   * Constructs a new ContentbirdWebhookEvent.
   *
   * @param array $data
   *   The webhook payload data.
   * @param string $event_type
   *   The webhook event type (e.g. 'content.status.changed').
   */
  public function __construct(array $data, string $event_type = '') {
    $this->data = $data;
    $this->eventType = $event_type;
  }

  /**
   * Gets the webhook payload data.
   *
   * @return array
   *   The webhook payload data.
   */
  public function getData(): array {
    return $this->data;
  }

  /**
   * Gets the webhook event type.
   *
   * @return string
   *   The event type string.
   */
  public function getEventType(): string {
    return $this->eventType;
  }

}
