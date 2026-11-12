<?php

declare(strict_types=1);

namespace Drupal\os2forms_f2\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_f2\Helper\F2Helper;
use Drupal\os2forms_f2\Settings;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTarget;
use Drupal\os2forms_f2\Settings\GeneralSettings\ArchiveTargetCase;
use Drupal\os2forms_f2\Settings\F2ApiSettings;
use Drupal\os2forms_f2\Settings\GeneralSettings;
use ItkDev\F2ApiClient\Model\Document;
use Psr\Log\LogLevel;
use ItkDev\F2ApiClient\Model\Matter\Type as MatterType;
use ItkDev\F2ApiClient\Model\Matter\Status as MatterStatus;
use ItkDev\F2ApiClient\Model\Matter\AccessLevel as MatterAccessLevel;

/**
 * Configure F2 settings for this site.
 */
final class SettingsForm extends ConfigFormBase {
  use StringTranslationTrait;
  use AutowireTrait;

  private const string ACTION_PING_API = 'action_ping_api';

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Settings $settings,
    protected F2Helper $f2,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getFormId(): string {
    return 'os2forms_f2_settings';
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
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
      '#title' => $this->t('F2 API', options: ['context' => 'os2forms_f2']),
      '#tree' => TRUE,
    ] + $this->buildFormF2Api();

    $form[GeneralSettings::NAME] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General', options: ['context' => 'os2forms_f2']),
      '#tree' => TRUE,
    ] + $this->buildFormGeneral();

    $form[self::ACTION_PING_API] = [
      '#type' => 'container',
      '#weight' => 10000,

      self::ACTION_PING_API => [
        '#type' => 'submit',
        '#name' => self::ACTION_PING_API,
        '#value' => $this->t('Ping API', options: ['context' => 'os2forms_f2']),
      ],

      'message' => [
        '#markup' => $this->t('Note: Pinging the API will use saved config.', options: ['context' => 'os2forms_f2']),
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
      '#title' => $this->t('URI', options: ['context' => 'os2forms_f2']),
      '#default_value' => $settings->uri,
      '#description' => $this->t('The F2 API base URI', options: ['context' => 'os2forms_f2']),
    ];

    $section[F2ApiSettings::USERNAME] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Username', options: ['context' => 'os2forms_f2']),
      '#default_value' => $settings->username,
      '#description' => $this->t('The F2 API username', options: ['context' => 'os2forms_f2']),
    ];

    $section[F2ApiSettings::SECRET] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Secret', options: ['context' => 'os2forms_f2']),
      '#default_value' => $settings->secret,
      '#description' => $this->t('The F2 API secret', options: ['context' => 'os2forms_f2']),
    ];

    $section[F2ApiSettings::F2_USERNAME] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('F2 username', options: ['context' => 'os2forms_f2']),
      '#default_value' => $settings->f2Username,
      '#description' => $this->t('The F2 username to act on behalf of', options: ['context' => 'os2forms_f2']),
    ];

    $section[F2ApiSettings::LOG_LEVEL] = [
      '#type' => 'select',
      '#options' => [
        LogLevel::EMERGENCY => $this->t('Emergency', options: ['context' => 'os2forms_f2']),
        LogLevel::ALERT     => $this->t('Alert', options: ['context' => 'os2forms_f2']),
        LogLevel::CRITICAL  => $this->t('Critical', options: ['context' => 'os2forms_f2']),
        LogLevel::ERROR     => $this->t('Error', options: ['context' => 'os2forms_f2']),
        LogLevel::WARNING   => $this->t('Warning', options: ['context' => 'os2forms_f2']),
        LogLevel::NOTICE    => $this->t('Notice', options: ['context' => 'os2forms_f2']),
        LogLevel::INFO      => $this->t('Info', options: ['context' => 'os2forms_f2']),
        LogLevel::DEBUG     => $this->t('Debug', options: ['context' => 'os2forms_f2']),
      ],
      '#required' => TRUE,
      '#title' => $this->t('API log level', options: ['context' => 'os2forms_f2']),
      '#default_value' => $settings->logLevel,
    ];

    return $section;
  }

  /**
   * Build form section "General".
   */
  private function buildFormGeneral(): array {
    $settings = $this->settings->getGeneralSettings();

    $section[GeneralSettings::MATTER_FILE_DOCUMENT_TITLE_DEFAULT_VALUE] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Matter file document title default value', options: ['context' => 'os2forms_f2']),
      '#description' => $this->t(<<<'DESCRIPTION'
Title used for file documents. Tokens can be used in the title, and in addition the regular webform submission token (e.g. <code>[webform_submission:label]</code>) these tokens can be used:
<ul>
<li><code>[file:name]</code> the name of the file without any extension, e.g. <code>my-document</code></li>
<li><code>[file:filename]</code> the full filename e.g. <code>my-document.pdf</code></li>
<li><code>[file:element:title]</code> the title of the file element</li>
</ul>
Max allowed length (after token expansion): @title_max_length.
DESCRIPTION, [
        '@title_max_length' => Document::TITLE_MAX_LENGTH,
      ], options: ['context' => 'os2forms_f2']),
      '#default_value' => $settings->matterFileDocumentTitleDefaultValue,
    ];

    $section[GeneralSettings::ARCHIVE_TARGETS] = [
      '#type' => 'checkboxes',
      '#options' => [
        ArchiveTarget::Case->value => $this->t('Case', options: ['context' => 'os2forms_f2']),
        ArchiveTarget::Matter->value => $this->t('Matter', options: ['context' => 'os2forms_f2']),
      ],
      '#required' => TRUE,
      '#title' => $this->t('Enabled archive targets', options: ['context' => 'os2forms_f2']),
      '#default_value' => $settings->archiveTargets,
    ];

    $section[ArchiveTargetCase::NAME] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings for "Archive on case"', options: ['context' => 'os2forms_f2']),

      ArchiveTargetCase::MATTER_TYPE_DEFAULT_VALUE => [
        '#type' => 'select',
        '#options' => [
          MatterType::Inbound->value => $this->t('Inbound', options: ['context' => 'os2forms_f2']),
          MatterType::Internal->value => $this->t('Internal', options: ['context' => 'os2forms_f2']),
          MatterType::Outbound->value => $this->t('Outbound', options: ['context' => 'os2forms_f2']),
        ],
        '#title' => $this->t('Default matter type', options: ['context' => 'os2forms_f2']),
        '#default_value' => $settings->archiveTargetCase->matterTypeDefaultValue->value,
        '#required' => TRUE,
      ],

      ArchiveTargetCase::MATTER_TYPE_ALLOW_OVERRIDE => [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow override', options: ['context' => 'os2forms_f2']),
        '#description' => $this->t('If set, a form builder can override the matter type in the F2 handler settings', options: ['context' => 'os2forms_f2']),
        '#default_value' => $settings->archiveTargetCase->matterTypeAllowOverride,
      ],

      ArchiveTargetCase::MATTER_STATUS_DEFAULT_VALUE => [
        '#type' => 'select',
        '#options' => [
          MatterStatus::InProgress->value => $this->t('In progress', options: ['context' => 'os2forms_f2']),
          MatterStatus::Closed->value => $this->t('Closed', options: ['context' => 'os2forms_f2']),
        ],
        '#title' => $this->t('Default matter status', options: ['context' => 'os2forms_f2']),
        '#default_value' => $settings->archiveTargetCase->matterStatusDefaultValue->value,
        '#required' => TRUE,
      ],

      ArchiveTargetCase::MATTER_STATUS_ALLOW_OVERRIDE => [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow override', options: ['context' => 'os2forms_f2']),
        '#description' => $this->t('If set, a form builder can override the matter status in the F2 handler settings', options: ['context' => 'os2forms_f2']),
        '#default_value' => $settings->archiveTargetCase->matterStatusAllowOverride,
      ],

      ArchiveTargetCase::MATTER_ACCESS_LEVEL_DEFAULT_VALUE => [
        '#type' => 'select',
        '#options' => [
          MatterAccessLevel::Involved->value => $this->t('Involved', options: ['context' => 'os2forms_f2']),
          MatterAccessLevel::Unit->value => $this->t('Unit', options: ['context' => 'os2forms_f2']),
          MatterAccessLevel::All->value => $this->t('All', options: ['context' => 'os2forms_f2']),
        ],
        '#title' => $this->t('Default matter access level', options: ['context' => 'os2forms_f2']),
        '#default_value' => $settings->archiveTargetCase->matterAccessLevelDefaultValue->value,
        '#required' => TRUE,
      ],

      ArchiveTargetCase::MATTER_ACCESS_LEVEL_ALLOW_OVERRIDE => [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow override', options: ['context' => 'os2forms_f2']),
        '#description' => $this->t('If set, a form builder can override the matter access level in the F2 handler settings', options: ['context' => 'os2forms_f2']),
        '#default_value' => $settings->archiveTargetCase->matterAccessLevelAllowOverride,
      ],

      ArchiveTargetCase::MATTER_ARCHIVED_DEFAULT_VALUE => [
        '#type' => 'checkbox',
        '#title' => $this->t('Archive matter by default?', options: ['context' => 'os2forms_f2']),
        '#default_value' => $settings->archiveTargetCase->matterArchivedDefaultValue,
      ],

      ArchiveTargetCase::MATTER_ARCHIVED_ALLOW_OVERRIDE => [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow override', options: ['context' => 'os2forms_f2']),
        '#description' => $this->t('If set, a form builder can override whether a not a matter is archived in the F2 handler settings', options: ['context' => 'os2forms_f2']),
        '#default_value' => $settings->archiveTargetCase->matterArchivedAllowOverride,
      ],
    ];

    $description = empty($settings->queue)
      ? $this->t('Queue for F2 jobs.', options: ['context' => 'os2forms_f2'])
      : $this->t("Queue for F2 jobs. <a href=':queue_url'>The queue</a> must be run via Drupal's cron or via <code>drush advancedqueue:queue:process @queue</code> (in a cron job).",
        [
          '@queue' => $settings->queue,
          ':queue_url' => '/admin/config/system/queues/jobs/' . urlencode((string) $settings->queue),
        ], options: ['context' => 'os2forms_f2']);
    $storage = $this->entityTypeManager->getStorage('advancedqueue_queue');
    $section[GeneralSettings::QUEUE] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Queue', options: ['context' => 'os2forms_f2']),
      '#options' => array_map(
        static fn(EntityInterface $queue) => $queue->label(),
        $storage->loadMultiple()
      ),
      '#empty_option' => $this->t('No queue', options: ['context' => 'os2forms_f2']),
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
        $this->messenger()->addStatus($this->t('Pinged API successfully.', options: ['context' => 'os2forms_f2']));
      }
      catch (\Throwable $t) {
        $previous = $t->getPrevious();
        $message = NULL !== $previous
          ? $this->t(
            'Pinging API failed: @message (@previous_message)',
            [
              '@message' => $t->getMessage(),
              '@previous_message' => $previous->getMessage(),
            ],
            options: ['context' => 'os2forms_f2']
          )
        : $this->t(
            'Pinging API failed: @message',
            [
              '@message' => $t->getMessage(),
            ],
            options: ['context' => 'os2forms_f2']
          );
        $this->messenger()->addError($message);
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
