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
   * @param array|null $body
   *   Optional request body (will be JSON-encoded).
   * @param array|null $headers
   *   Optional additional headers to include in the request.
   *
   * @return mixed
   *   The decoded JSON response data, or FALSE on failure.
   */
  public function request(string $method, string $endpoint, ?array $body = NULL, ?array $headers = NULL): mixed;

  /**
   * Gets the full URL for a named API endpoint.
   *
   * @param string $endpoint_name
   *   The endpoint name as configured (e.g. 'get-ids', 'get-keywords/{projectId}').
   * @param array|null $params
   *   Optional parameters to replace in the endpoint path if needed.
   * 
   * @return string
   *   The full URL for the endpoint.
   */
  public function getEndpointUrl(string $endpoint_name, ?array $params = NULL): string;

  // ---------------------------------------------------------------------------
  // Utils
  // ---------------------------------------------------------------------------

  /**
   * Retrieves a list of all IDs the contentbird platform is using for relations.
   *
   * @param string $language
   *   The language code for the data (e.g. 'en').
   * 
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function getListOfIds(string $language = 'en'): mixed;

  /**
   * Retrieves a list of all keywords (including metrics) of the given project.
   * 
   * @param int $project_id
   *   The contentbird project ID.
   * @param string $language
   *   The language code for the data (e.g. 'en').
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function getProjectKeywords(int $project_id, string $language = 'en'): mixed;

  /**
   * Retrieves a list of all active connected social profiles of the given project.
   * 
   * @param int $project_id
   *   The contentbird project ID.
   * @param string $language
   *   The language code for the data (e.g. 'en').
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function getProjectSocialProfiles(int $project_id, string $language = 'en'): mixed;

  // ---------------------------------------------------------------------------
  // Content operations
  // ---------------------------------------------------------------------------

  /**
   * Creates a new content item in contentbird.
   *
   * @param array $data
   *   The content data. Expected keys may include:
   *   - title: (string) The content title.
   *   - project_id: (int) The contentbird project ID to associate with.
   *   - type_id: (int) The content type ID.
   *   - language: (string) The content language (e.g. 'en').
   *   - status_id: (int) The initial content status ID.
   *   - release_date: (string) The release date in ISO-8601 format.
   *   - manager_user_id: (int) The user ID of the content manager.
   *   - author_user_id: (int) The user ID of the content author.
   *   - story_id: (int) The story ID to associate with.
   *   - briefing: (string) The content briefing.
   *   - keyword_ids: (array) An array of keyword IDs to associate with.
   *   - resources_files: (array) An array of file resource data. Each item should have:
   *    - name: (string) The file name.
   *    - url: (string) The file URL.
   *   - resources_urls: (array) An array of URL resource data. Each item should have:
   *    - name: (string) The resource name.
   *    - url: (string) The resource URL.
   *   - persona_id: (int) The persona ID to associate with.
   *   - content_target_id: (int) The content target ID to associate with.
   *   - call_to_action_id: (int) The call to action ID to associate with.
   *   - content: (string) The HTML content body.
   *   - customFields: (array) Custom field data. Each item should have:
   *    - id: (int) The custom field ID.
   *    - value: (mixed) The value for the custom field.
   *   - customElements: (array) Custom element data. Each item should have:
   *    - id: (int) The custom element ID.
   *    - value: (mixed) The value for the custom element.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function createContent(array $data): mixed;

  /**
   * Fetches multiple content items, optionally filtered.
   *
   * @param array $filters
   *   Optional filters for the content items (status, project id, language).
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function getContents(array $filters = []): mixed;

  /**
   * Fetches a single content item by its contentbird ID.
   *
   * @param int $content_id
   *   The contentbird content ID.
   * @param string $language
   *   The language code for the content (e.g. 'en').
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function getContent(int $content_id, string $language = 'en'): mixed;

  /**
   * Updates an existing content item in contentbird.
   *
   * @param int $content_id
   *   The contentbird content ID.
   * @param array $data
   *   The fields to update. See the createContent() method for possible field keys.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function updateContent(int $content_id, array $data): mixed;

  /**
   * Updates the content status to published in contentbird.
   *
   * @param int $content_id
   *   The contentbird content ID.
   * @param string $published_url
   *   The URL where the content was published.
   * @param string $published_at
   *   The publish date in ISO-8601 format.
   * @param float|null $cost
   *   The cost of the content (optional).
   * @param int|null $status_id
   *   Optional content status ID. If not provided, the default published status will be used.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function publishContent(int $content_id, string $published_url, string $published_at, ?float $cost = NULL, ?int $status_id = NULL): mixed;

  /**
   * Updates the content status to unpublished in contentbird.
   *
   * @param int $content_id
   *   The contentbird content ID.
   * @param int $status_id
   *   The content status ID to set when unpublishing.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function unpublishContent(int $content_id, int $status_id): mixed;

  /**
   * Creates a new version of an existing content item in contentbird.
   *
   * @param int $content_id
   *   The contentbird content ID.
   * @param array $data
   *   The content data for the new version. See the createContent() method for possible field keys.
   *
   * @return mixed
   *   The decoded response data, or FALSE on failure.
   */
  public function createContentVersion(int $content_id, array $data): mixed;

  // ---------------------------------------------------------------------------
  // Social posts
  // ---------------------------------------------------------------------------

  /**
   * Creates a social post in contentbird.
   *
   * @param array $data
   *   The social post data.
   *   Required keys:
   *   - project_id: (int) The contentbird project ID to associate with.
   *   - page_id: (int) The social profile/page ID to post to.
   *   - language: (string) The post language (e.g. 'en').
   *   - post_content: (string) The text content of the post.
   *   Additional optional keys supported:
   *   - image_attachments: (array) An array of image attachment urls.
   *   - video_attachments: (array) An array of video attachment urls.
   *   - promote_url: (string) A URL to promote in the post. Only supported for Facebook and Linkedin.
   *   - type: (string) The post type (e.g. 'draft', 'publish_at', 'publish_now').
   *   - publish_at: (string) The scheduled publish date in Unix timestamp format (required if type is 'publish_at').
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
