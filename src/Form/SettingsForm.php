<?php

declare(strict_types=1);

namespace Drupal\os2forms_f2\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_f2\Helper\F2Helper;
use Drupal\os2forms_f2\Settings;
use Drupal\os2forms_f2\Settings\F2ApiSettings;
use Drupal\os2forms_f2\Settings\GeneralSettings;

/**
 * Configure F2 settings for this site.
 */
final class SettingsForm extends ConfigFormBase {
  use StringTranslationTrait;
  use AutowireTrait;

  private const string ACTION_PING_API = 'action_ping_api';

  /**
   * The queue storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private readonly EntityStorageInterface $queueStorage;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    EntityTypeManagerInterface $entityTypeManager,
    private readonly Settings $settings,
    private readonly F2Helper $f2,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->queueStorage = $entityTypeManager->getStorage('advancedqueue_queue');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'os2forms_f2_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [Settings::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $form[F2ApiSettings::NAME] = [
      '#type' => 'fieldset',
      '#title' => $this->t('F2 API'),
      '#tree' => TRUE,
    ] + $this->buildFormF2Api();

    $form[GeneralSettings::NAME] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General'),
      '#tree' => TRUE,
    ] + $this->buildFormGeneral();

    $form[self::ACTION_PING_API] = [
      '#type' => 'container',
      '#weight' => 10000,

      self::ACTION_PING_API => [
        '#type' => 'submit',
        '#name' => self::ACTION_PING_API,
        '#value' => $this->t('Ping API'),
      ],

      'message' => [
        '#markup' => $this->t('Note: Pinging the API will use saved config.'),
      ],
    ];

    return $form;
  }

  /**
   * Build form section "F2 API".
   */
  private function buildFormF2Api(): array {
    $settings = $this->settings->getF2ApiSettings();

    $section[F2ApiSettings::URI] = [
      '#type' => 'url',
      '#required' => TRUE,
      '#title' => $this->t('URI'),
      '#default_value' => $settings->uri,
      '#description' => $this->t('The F2 API base URI'),
    ];

    $section[F2ApiSettings::USERNAME] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Username'),
      '#default_value' => $settings->username,
      '#description' => $this->t('The F2 API username'),
    ];

    $section[F2ApiSettings::SECRET] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Secret'),
      '#default_value' => $settings->secret,
      '#description' => $this->t('The F2 API secret'),
    ];

    $section[F2ApiSettings::F2_USERNAME] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('F2 username'),
      '#default_value' => $settings->f2Username,
      '#description' => $this->t('The F2 username to act on behalf of'),
    ];

    return $section;
  }

  /**
   * Build form section "General".
   */
  private function buildFormGeneral(): array {
    $settings = $this->settings->getGeneralSettings();

    $description = empty($settings->queue)
      ? $this->t('Queue for F2 jobs.')
      : $this->t("Queue for F2 jobs. <a href=':queue_url'>The queue</a> must be run via Drupal's cron or via <code>drush advancedqueue:queue:process @queue</code> (in a cron job).",
        [
          '@queue' => $settings->queue,
          ':queue_url' => '/admin/config/system/queues/jobs/' . urlencode((string) $settings->queue),
        ]);
    $section[GeneralSettings::QUEUE] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Queue'),
      '#options' => array_map(
        static fn(EntityInterface $queue) => $queue->label(),
        $this->queueStorage->loadMultiple()
      ),
      '#empty_option' => $this->t('No queue'),
      '#default_value' => $settings->queue,
      '#description' => $description,
    ];

    return $section;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (self::ACTION_PING_API === ($form_state->getTriggeringElement()['#name'] ?? NULL)) {
      return;
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (self::ACTION_PING_API === ($form_state->getTriggeringElement()['#name'] ?? NULL)) {
      try {
        $this->f2->pingApi();
        $this->messenger()->addStatus($this->t('Pinged API successfully.'));
      }
      catch (\Throwable $t) {
        $this->messenger()->addError($this->t('Pinging API failed: @message', ['@message' => $t->getMessage()]));
      }
      return;
    }

    $config = $this->config(Settings::CONFIG_NAME);
    foreach ([
      F2ApiSettings::NAME,
      GeneralSettings::NAME,
    ] as $name) {
      $config->set($name, $form_state->getValue($name));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
