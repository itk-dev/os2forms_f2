<?php

namespace Drupal\os2forms_f2\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_f2\Helper\F2Helper;
use Drupal\os2forms_f2\Helper\WebformHelperF2;
use Drupal\os2forms_f2\Settings;
use Drupal\os2forms_f2\Settings\ArchiveSettings;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTarget;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTargetMatter;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * F2 Webform Handler.
 *
 * @WebformHandler(
 *   id = "os2forms_f2",
 *   label = @Translation("F2"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Sends webform submission to F2."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class WebformHandlerF2 extends WebformHandlerBase {
  use StringTranslationTrait;

  public const string ID = 'os2forms_f2_f2';

  /**
   * The settings.
   */
  private Settings $settingsService;

  /**
   * The webform helper.
   */
  private WebformHelperF2 $helper;

  /**
   * The F2 helper.
   */
  private F2Helper $f2;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->settingsService = $container->get(Settings::class);
    $instance->helper = $container->get(WebformHelperF2::class);
    $instance->f2 = $container->get(F2Helper::class);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getOffCanvasWidth(): string {
    return WebformDialogHelper::DIALOG_NONE;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $settings = $this->settingsService->getArchiveSettings((array) ($this->getSetting(ArchiveSettings::NAME)));

    $form[ArchiveSettings::NAME] = [
      ArchiveSettings::ATTACHMENT_ELEMENT => [
        '#type' => 'select',
        '#required' => TRUE,
        '#title' => $this->t('Attachment element'),
        '#default_value' => $settings->attachmentElement,
        '#options' => $this->getAttachmentElements(),
      ],

      ArchiveSettings::ARCHIVE_TARGET => [
        '#type' => 'select',
        '#required' => TRUE,
        '#title' => $this->t('Archive target'),
        '#default_value' => $settings->archiveTarget?->value,
        '#options' => [
          ArchiveTarget::MatterID->value => $this->t('Matter ID'),
        ],
      ],

      ArchiveTargetMatter::NAME => [
        ArchiveTargetMatter::MATTER_ID => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Matter ID'),
          '#default_value' => $settings->archiveTargetMatter?->matterId,

          '#states' => [
            'visible' => [
              ':input[name="settings[' . ArchiveSettings::NAME . '][' . ArchiveTargetMatter::NAME . ']"]' => [
                'value' => ArchiveTarget::MatterID->value,
              ],
            ],
          ],
        ],
      ],
    ];

    if (ArchiveTarget::MatterID === $settings->archiveTarget) {
      $matterId = $settings->archiveTargetMatter?->matterId;
      if (NULL !== $matterId) {
        try {
          $matter = $this->f2->client()->matterById($matterId);

          $form[ArchiveSettings::NAME][ArchiveTargetMatter::NAME]['details'] = [
            '#type' => 'details',
            '#open' => TRUE,
            '#title' => $this->t('Matter'),
            '#markup' => $matter,
          ];
        }
        catch (\Throwable $e) {
          // Ignore all errors.
        }
      }
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $target = $form_state->getValue([ArchiveSettings::NAME, ArchiveSettings::ARCHIVE_TARGET]);
    if (ArchiveTarget::MatterID->value === $target) {
      $key = [ArchiveSettings::NAME, ArchiveTargetMatter::NAME, ArchiveTargetMatter::MATTER_ID];
      $matterId = trim((string) $form_state->getValue($key));
      $matterId = filter_var($matterId, FILTER_SANITIZE_NUMBER_INT);
      if (FALSE === $matterId) {
        $form_state->setErrorByName(implode('][', $key), t('Missing or invalid matter ID.'));
      }
      else {
        try {
          $this->f2->client()->matterById($matterId);
        }
        catch (\Throwable $throwable) {
          $form_state->setErrorByName(implode('][', $key), t('Cannot get matter by ID @matter_id (@message).', [
            '@matter_id' => $matterId,
            '@message' => $throwable->getMessage(),
          ]));
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    foreach ([
      ArchiveSettings::NAME,
    ] as $name) {
      $this->configuration[$name] = $form_state->getValue($name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // Run only when submission is completed.
    // @todo Run on update?
    if (!$webform_submission->isCompleted()) {
      return;
    }

    $this->helper->createJob($webform_submission, $this);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getSummary() {
    $settings = $this->settingsService->getHandlerSettings($this);

    $build = [
      'info' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ],
    ];

    switch ($settings->archive?->archiveTarget) {
      case ArchiveTarget::MatterID:
        $matterId = $settings->archive->archiveTargetMatter?->matterId;
        if ($matterId) {
          $build['info'][ArchiveTargetMatter::NAME] = [
            '#markup' => $this->t('Archive on matter @matter_id', ['@matter_id' => $matterId]),
          ];
        }
        break;
    }

    return $build;
  }

  /**
   * Get attachment elements.
   *
   * @phpstan-return array<string, mixed>
   */
  private function getAttachmentElements(): array {
    $elements = $this->getWebform()->getElementsDecodedAndFlattened();

    $elementTypes = [
      'webform_entity_print_attachment:pdf',
      'os2forms_attachment',
    ];
    $elements = array_filter(
      $elements,
      static fn(array $element) => in_array($element['#type'], $elementTypes, TRUE)
    );

    return array_map(static fn(array $element) => $element['#title'], $elements);
  }

}
