# IQ Contentbird API

Drupal 11 module providing integration with the [contentbird Integration API](https://api.docs.mycontentbird.io/?version=latest).

This module provides a configurable API client service and webhook endpoint to interface between Drupal and the contentbird content management platform.

## Features

- **API Token Authentication**: Secure communication via the `X-ContentbirdApiToken` header.
- __Admin Settings Form__: Configure API token, base URL, and endpoints at `admin/config/services/iq_contentbird_api`.
- __API Client Service__: Injectable service (`iq_contentbird_api.client`) for communicating with contentbird:
   - Fetch content statuses, custom elements, and custom fields (Utils).
   - Create, fetch, and update content items.
   - Update content status (e.g., "CMS imported", "Published").
   - Create social posts.

- __Webhook Endpoint__: Receives push events from contentbird at `/iq_contentbird_api/webhook`.
- **Event System**: Dispatches `ContentbirdWebhookEvent` so other modules can subscribe and react to webhook events.

## Requirements

- Drupal 11
- PHP 8.3+
- Guzzle HTTP client (included with Drupal core)

## Installation

1. Install the module via Composer `composer require iqual/iq_contentbird_api`
2. Enable the module: `drush en iq_contentbird_api`
3. Navigate to **Admin > Configuration > Web services > Contentbird API Settings**.
4. Enter your contentbird API token (generated under Setup > System > Integrations > Api authentication in contentbird).
5. Verify the connection status shows as "Connected successfully".

## Configuration

### API Token

Generate an API token in contentbird under **Setup > System > Integrations > Api authentication** and paste it into the Drupal settings form.

### Webhook Setup

1. Note the webhook URL shown on the settings page (e.g., `https://your-site.com/iq_contentbird_api/webhook`).
2. Configure this URL in your contentbird webhook settings.
3. Optionally set a webhook secret for payload signature verification.

## Usage

### Using the API Client Service

Inject the service `iq_contentbird_api.client` in your custom code:

    // In a controller or service using dependency injection:
    public function __construct(
      private ContentbirdApiClientInterface $contentbirdClient,
    ) {}
    
    // Fetch list ids.
    $list_ids = $this->contentbirdClient->getListOfIds();
    
    // Fetch a single content item.
    $content = $this->contentbirdClient->getContent(12345);
    
    // Update content status to a published status (status ID from getListOfIds()).
    $this->contentbirdClient->updateStatusPublishedContent(
      content_id: 12345,
      status_id: 3,
      published_url: 'https://your-site.com/node/42',
    );
    
    // Create a social post.
    $this->contentbirdClient->createSocialPost([
      'contentId' => 12345,
      'text' => 'Check out our new article!',
    ]);

### Subscribing to Webhook Events

Create an event subscriber in your module:

    namespace Drupal\my_module\EventSubscriber;
    
    use Drupal\iq_contentbird_api\Event\ContentbirdWebhookEvent;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    
    class ContentbirdWebhookSubscriber implements EventSubscriberInterface {
    
      public static function getSubscribedEvents() {
        return [
          ContentbirdWebhookEvent::WEBHOOK_RECEIVED => 'onWebhookReceived',
        ];
      }
    
      public function onWebhookReceived(ContentbirdWebhookEvent $event) {
        $data = $event->getData();
        $eventType = $event->getEventType();
        // Process the webhook data, e.g., create/update Drupal nodes.
      }
    
    }

Register the subscriber in your module's services YAML file:

    services:
      my_module.contentbird_webhook_subscriber:
        class: Drupal\my_module\EventSubscriber\ContentbirdWebhookSubscriber
        tags:
          - { name: event_subscriber }

## Typical Integration Workflow

1. Content is created and approved in contentbird.
2. Contentbird sends a webhook to your Drupal site (or you pull via API).
3. Your custom subscriber creates a Drupal node draft with the contentbird content.
4. The module updates the contentbird status to "CMS imported".
5. When the Drupal node is published, update the contentbird status with the published URL.

## API Reference

See the full [contentbird Integration API documentation](https://api.docs.mycontentbird.io/?version=latest).

## License

This project is licensed under the GPL-2.0-or-later license.