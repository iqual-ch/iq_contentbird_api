<?php

namespace Drupal\iq_contentbird_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\iq_contentbird_api\Service\ContentbirdApiClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contentbird API settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The Contentbird API client.
   *
   * @var \Drupal\iq_contentbird_api\Service\ContentbirdApiClientInterface
   */
  protected ContentbirdApiClientInterface $contentbirdApiClient;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\iq_contentbird_api\Service\ContentbirdApiClientInterface $contentbird_api_client
   *   The Contentbird API client.
   */
  public function __construct(
    ContentbirdApiClientInterface $contentbird_api_client,
  ) {
    $this->contentbirdApiClient = $contentbird_api_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('iq_contentbird_api.client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'iq_contentbird_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['iq_contentbird_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('iq_contentbird_api.settings');

    // Authentication settings fieldset.
    $form['auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Authentication Settings'),
    ];

    $form['auth']['help'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('To get your API token, log in to contentbird and navigate to Setup / System / Integrations / Api authentication. See the @link for details.', [
        '@link' => Link::fromTextAndUrl(
          'contentbird API documentation',
          Url::fromUri('https://api.docs.mycontentbird.io/?version=latest')
        )->toString(),
      ]) . '</p>',
    ];

    $form['auth']['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#default_value' => $config->get('api_token'),
      '#required' => TRUE,
      '#description' => $this->t('The API token used for the X-ContentbirdApiToken request header.'),
      '#maxlength' => 512,
    ];

    $form['auth']['webhook_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook Secret'),
      '#default_value' => $config->get('webhook_secret'),
      '#description' => $this->t('Optional secret to validate incoming webhook requests from contentbird. If set, webhook payloads will be verified against this secret.'),
      '#maxlength' => 512,
    ];

    // Connection status.
    if (!empty($config->get('api_token'))) {
      $form['auth']['connection_status'] = [
        '#type' => 'details',
        '#title' => $this->t('Connection Status'),
        '#open' => TRUE,
      ];

      $statuses = $this->contentbirdApiClient->getListOfIds();
      if ($statuses !== FALSE) {
        $form['auth']['connection_status']['status'] = [
          '#markup' => '<p><strong>' . $this->t('Connected successfully.') . '</strong> ' . $this->t('The API token is valid and the connection to contentbird is working.') . '</p>',
        ];
      }
      else {
        $form['auth']['connection_status']['status'] = [
          '#markup' => '<p><strong>' . $this->t('Connection failed.') . '</strong> ' . $this->t('Please verify your API token and base URL. Check the logs for details.') . '</p>',
        ];
        $this->messenger()->addWarning($this->t('Could not connect to the contentbird API. Please verify your settings.'));
      }
    }

    // API settings fieldset.
    $form['api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Settings'),
    ];

    $form['api_settings']['api_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Base URL'),
      '#default_value' => $config->get('api_base_url'),
      '#required' => TRUE,
      '#description' => $this->t('Default: https://api.live.mycontentbird.io/api/cms'),
    ];

    // Webhook information.
    $form['webhook_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Webhook Endpoint'),
    ];

    $webhook_url = Url::fromRoute('iq_contentbird_api.webhook', [], ['absolute' => TRUE])->toString();
    $form['webhook_info']['info'] = [
      '#markup' => '<p>' . $this->t('Configure the following URL in your contentbird webhook settings to receive push events:') . '</p><p><code>' . $webhook_url . '</code></p>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('iq_contentbird_api.settings')
      ->set('api_token', $form_state->getValue('api_token'))
      ->set('api_base_url', $form_state->getValue('api_base_url'))
      ->set('webhook_secret', $form_state->getValue('webhook_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
