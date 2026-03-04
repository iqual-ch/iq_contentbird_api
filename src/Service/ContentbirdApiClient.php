<?php

namespace Drupal\iq_contentbird_api\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * Contentbird API Client service.
 *
 * Handles API token authentication and requests to the contentbird platform.
 *
 * @see https://api.docs.mycontentbird.io/?version=latest
 */
class ContentbirdApiClient implements ContentbirdApiClientInterface {

  use StringTranslationTrait;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a ContentbirdApiClient object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    protected ClientInterface $httpClient,
    protected MessengerInterface $messenger,
  ) {
    $this->config = $config_factory->get('iq_contentbird_api.settings');
    $this->logger = $logger_factory->get('iq_contentbird_api');
  }

  /**
   * {@inheritdoc}
   */
  public function request(string $method, string $endpoint, ?int $project_id = NULL, ?array $query = NULL, ?array $body = NULL): mixed {
    $apiToken = $this->config->get('api_token');

    if (empty($apiToken)) {
      $this->logger->error('No API token configured. Please configure the Contentbird API settings.');
      return FALSE;
    }

    $request_options = [
      RequestOptions::HEADERS => [
        'X-ContentbirdApiToken' => 'Bearer ' . $apiToken,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
    ];

    if (!empty($body)) {
      $request_options[RequestOptions::BODY] = json_encode($body);
    }

    if (!empty($query)) {
      $request_options[RequestOptions::QUERY] = $query;
    }

    try {
      $response = $this->httpClient->request($method, $endpoint, $request_options);
    }
    catch (GuzzleException $exception) {
      $statusCode = 0;
      if (method_exists($exception, 'getResponse') && $exception->getResponse()) {
        $statusCode = $exception->getResponse()->getStatusCode();
      }

      // Handle 401 Unauthorized.
      if ($statusCode === 401 || str_contains($exception->getMessage(), '401')) {
        $this->logger->error('Authentication failed: Invalid or missing API token. Please verify your Contentbird API token.');
        return FALSE;
      }

      // Handle 400 Bad Request.
      if ($statusCode === 400 || str_contains($exception->getMessage(), '400')) {
        $this->logger->error('Bad request to Contentbird API: @error', [
          '@error' => $exception->getMessage(),
        ]);
        return FALSE;
      }

      // Handle 404 Not Found.
      if ($statusCode === 404 || str_contains($exception->getMessage(), '404')) {
        $this->logger->warning('Contentbird API resource not found: @endpoint', [
          '@endpoint' => $endpoint,
        ]);
        return FALSE;
      }

      $this->logger->error('Contentbird API request failed: @error', [
        '@error' => $exception->getMessage(),
      ]);
      return FALSE;
    }

    $statusCode = $response->getStatusCode();

    if ($statusCode === 401) {
      $this->logger->error('Authentication failed: Invalid or missing API token.');
      return FALSE;
    }

    $responseBody = (string) $response->getBody();
    if (empty($responseBody)) {
      return TRUE;
    }

    return Json::decode($responseBody);
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpointUrl(string $endpoint_name, ?int $project_id = NULL): string {
    $base = rtrim($this->config->get('api_base_url'), '/');
    if ($project_id !== NULL) {
      $endpoint_name = str_replace('{projectId}', $project_id, $endpoint_name);
    }
    return $base . $endpoint_name;
  }

  // ---------------------------------------------------------------------------
  // Utils
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function getListOfIds(): mixed {
    return $this->request('GET', $this->getEndpointUrl('get-ids'));
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectKeywords(int $project_id): mixed {
    return $this->request('GET', $this->getEndpointUrl('get-keywords/{projectId}', $project_id));
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectSocialProfiles(int $project_id): mixed {
    return $this->request('GET', $this->getEndpointUrl('get-social-profiles/{projectId}', $project_id));
  }

  // ---------------------------------------------------------------------------
  // Content operations
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function getContent(int $content_id): mixed {
    $url = $this->getEndpointUrl('content') . '/' . $content_id;
    return $this->request('GET', $url);
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(array $query = []): mixed {
    return $this->request('GET', $this->getEndpointUrl('content'), $query);
  }

  /**
   * {@inheritdoc}
   */
  public function createContent(array $data): mixed {
    return $this->request('POST', $this->getEndpointUrl('content'), NULL, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function updateContent(int $content_id, array $data): mixed {
    $url = $this->getEndpointUrl('content') . '/' . $content_id;
    return $this->request('PUT', $url, NULL, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function updateContentStatus(int $content_id, int $status_id, ?string $published_url = NULL, ?string $published_at = NULL): mixed {
    $data = [
      'contentStatusId' => $status_id,
    ];

    if ($published_url !== NULL) {
      $data['publishedUrl'] = $published_url;
    }

    if ($published_at !== NULL) {
      $data['publishedAt'] = $published_at;
    }

    return $this->updateContent($content_id, $data);
  }

  // ---------------------------------------------------------------------------
  // Social posts
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function createSocialPost(array $data): mixed {
    return $this->request('POST', $this->getEndpointUrl('social_post'), NULL, $data);
  }

  // ---------------------------------------------------------------------------
  // HTTP client access
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function getHttpClient(): ClientInterface {
    return $this->httpClient;
  }

}
