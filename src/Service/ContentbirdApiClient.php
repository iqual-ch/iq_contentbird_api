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
  public function request(string $method, string $endpoint, ?array $body = NULL, ?array $headers = NULL): mixed {
    $apiToken = $this->config->get('api_token');

    if (empty($apiToken)) {
      $this->logger->error('No API token configured. Please configure the Contentbird API settings.');
      return FALSE;
    }

    $headersDefaults = [
      'X-ContentbirdApiToken' => 'Bearer ' . $apiToken,
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    ];
    $headers = array_merge($headersDefaults, $headers ?? []);
    $request_options = [
      RequestOptions::HEADERS => $headers,
    ];

    if (!empty($body)) {
      $request_options[RequestOptions::BODY] = Json::encode($body);
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
      if ($statusCode === 401) {
        $this->logger->error('Authentication failed: Invalid or missing API token. Please verify your Contentbird API token.');
        return FALSE;
      }

      // Handle 400 Bad Request.
      if ($statusCode === 400) {
        $this->logger->error('Bad request to Contentbird API: @error', [
          '@error' => $exception->getMessage(),
        ]);
        return FALSE;
      }

      // Handle 404 Not Found.
      if ($statusCode === 404) {
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

    $responseBody = (string) $response->getBody();
    if (empty($responseBody)) {
      return TRUE;
    }

    return Json::decode($responseBody);
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpointUrl(string $endpoint_name, ?array $params = NULL): string {
    $base = rtrim($this->config->get('api_base_url'), '/');
    if ($params !== NULL) {
      foreach ($params as $key => $value) {
        if ($value !== NULL) {
          $endpoint_name = str_replace('{' . $key . '}', $value, $endpoint_name);
        }
      }
    }
    return $base . '/' . $endpoint_name;
  }

  // ---------------------------------------------------------------------------
  // Utils
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function getListOfIds(string $language = 'en'): mixed {
    return $this->request('GET', $this->getEndpointUrl('get-ids'), NULL, ['X-ContentbirdLocale' => $language]);
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectKeywords(int $project_id, string $language = 'en'): mixed {
    return $this->request('GET', $this->getEndpointUrl('get-keywords/{projectId}', ['projectId' => $project_id]), NULL, ['X-ContentbirdLocale' => $language]);
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectSocialProfiles(int $project_id, string $language = 'en'): mixed {
    return $this->request('GET', $this->getEndpointUrl('get-social-profiles/{projectId}', ['projectId' => $project_id]), NULL, ['X-ContentbirdLocale' => $language]);
  }

  // ---------------------------------------------------------------------------
  // Content operations
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function createContent(array $data): mixed {
    return $this->request('POST', $this->getEndpointUrl('create-content'), $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(array $filters = []): mixed {
    return $this->request('POST', $this->getEndpointUrl('get-contents'), $filters);
  }

  /**
   * {@inheritdoc}
   */
  public function getContent(int $content_id, string $language = 'en'): mixed {
    $url = $this->getEndpointUrl('get-content/{contentId}', ['contentId' => $content_id]);
    return $this->request('GET', $url, NULL, ['X-ContentbirdLocale' => $language]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateContent(int $content_id, array $data): mixed {
    $url = $this->getEndpointUrl('update-content/{contentId}', ['contentId' => $content_id]);
    return $this->request('PUT', $url, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function publishContent(int $content_id, string $published_url, string $published_at, ?float $cost = NULL, ?int $status_id = NULL): mixed {
    $data = [
      'url' => $published_url,
      'publishedDate' => $published_at,
    ];
    if ($cost !== NULL) {
      $data['cost'] = $cost;
    }
    if ($status_id !== NULL) {
      $data['status'] = $status_id;
    }

    return $this->request('PUT', $this->getEndpointUrl('publish-content/{contentId}', ['contentId' => $content_id]), $data);
  }

  /**
   * {@inheritdoc}
   */
  public function unpublishContent(int $content_id, int $status_id): mixed {
    $params = [
      'contentId' => $content_id,
      'statusId' => $status_id,
    ];
    return $this->request('PUT', $this->getEndpointUrl('update-content-status/{contentId}/status/{statusId}', $params));
  }

  /**
   * {@inheritdoc}
   */
  public function createContentVersion(int $content_id, array $data): mixed {
    return $this->request('POST', $this->getEndpointUrl('create-content-version/{contentId}', ['contentId' => $content_id]), $data);
  }

  // ---------------------------------------------------------------------------
  // Social posts
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function createSocialPost(array $data): mixed {
    return $this->request('POST', $this->getEndpointUrl('create-social-post'), $data);
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
