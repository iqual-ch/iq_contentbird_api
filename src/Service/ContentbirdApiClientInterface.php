<?php

namespace Drupal\iq_contentbird_api\Service;

use GuzzleHttp\ClientInterface;

/**
 * Interface for the Contentbird API client service.
 */
interface ContentbirdApiClientInterface {

  /**
   * Makes an authenticated API request to the contentbird platform.
   *
   * @param string $method
   *   The HTTP method (GET, POST, PUT, PATCH, DELETE).
   * @param string $endpoint
   *   The full API endpoint URL.
   * @param array|null $query
   *   Optional query parameters.
   * @param array|null $body
   *   Optional request body (will be JSON-encoded).
   *
   * @return mixed
   *   The decoded JSON response data, or FALSE on failure.
   */
  public function request(string $method, string $endpoint, ?array $query = NULL, ?array $body = NULL): mixed;

  /**
   * Gets the full URL for a named API endpoint.
   *
   * @param string $endpoint_name
   *   The endpoint name as configured (e.g. 'content', 'content_statuses').
   *
   * @return string
   *   The full URL for the endpoint.
   */
  public function getEndpointUrl(string $endpoint_name): string;

  // ---------------------------------------------------------------------------
  // Utils
  // ---------------------------------------------------------------------------

  /**
   * Retrieves the available content statuses from contentbird.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function getContentStatuses(): mixed;

  /**
   * Retrieves the custom elements configuration from contentbird.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function getCustomElements(): mixed;

  /**
   * Retrieves the custom fields configuration from contentbird.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function getCustomFields(): mixed;

  // ---------------------------------------------------------------------------
  // Content operations
  // ---------------------------------------------------------------------------

  /**
   * Fetches a single content item by its contentbird ID.
   *
   * @param int $content_id
   *   The contentbird content ID.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function getContent(int $content_id): mixed;

  /**
   * Fetches multiple content items, optionally filtered.
   *
   * @param array $query
   *   Optional query parameters for filtering (e.g. status, page, limit).
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function getContents(array $query = []): mixed;

  /**
   * Creates a new content item in contentbird.
   *
   * @param array $data
   *   The content data. Expected keys may include:
   *   - title: (string) The content title.
   *   - content: (string) The HTML content body.
   *   - contentStatusId: (int) The content status ID.
   *   - customElements: (array) Custom element data.
   *   - customFields: (array) Custom field data.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function createContent(array $data): mixed;

  /**
   * Updates an existing content item in contentbird.
   *
   * @param int $content_id
   *   The contentbird content ID.
   * @param array $data
   *   The fields to update. Possible keys:
   *   - contentStatusId: (int) The new content status ID.
   *   - publishedUrl: (string) The published URL of the content.
   *   - publishedAt: (string) The publish date in ISO-8601 format.
   *   - title: (string) The content title.
   *   - content: (string) The content body.
   *   - customElements: (array) Custom element values.
   *   - customFields: (array) Custom field values.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function updateContent(int $content_id, array $data): mixed;

  /**
   * Updates the content status in contentbird.
   *
   * This is a convenience method for updating only the status and optional
   * published URL of a content item.
   *
   * @param int $content_id
   *   The contentbird content ID.
   * @param int $status_id
   *   The new content status ID.
   * @param string|null $published_url
   *   Optional URL where the content was published.
   * @param string|null $published_at
   *   Optional publish date in ISO-8601 format.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function updateContentStatus(int $content_id, int $status_id, ?string $published_url = NULL, ?string $published_at = NULL): mixed;

  // ---------------------------------------------------------------------------
  // Social posts
  // ---------------------------------------------------------------------------

  /**
   * Creates a social post in contentbird.
   *
   * @param array $data
   *   The social post data.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function createSocialPost(array $data): mixed;

  // ---------------------------------------------------------------------------
  // HTTP client access
  // ---------------------------------------------------------------------------

  /**
   * Returns the underlying HTTP client.
   *
   * @return \GuzzleHttp\ClientInterface
   *   The Guzzle HTTP client.
   */
  public function getHttpClient(): ClientInterface;

}
