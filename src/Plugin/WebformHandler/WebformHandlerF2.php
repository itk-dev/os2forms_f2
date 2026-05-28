<?php

namespace Drupal\os2forms_f2\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_f2\Helper\WebformHelperF2;
use Drupal\os2forms_f2\Settings;
use Drupal\os2forms_f2\Settings\ArchiveSettings;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTarget;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTargetCase;
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
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->settingsService = $container->get(Settings::class);
    $instance->helper = $container->get(WebformHelperF2::class);

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
    $settings = $this->settingsService->getArchiveSettings((array) ($this->getSettings()[ArchiveSettings::NAME] ?? NULL));

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
          ArchiveTarget::CaseID->value => $this->t('CaseID'),
        ],
      ],

      ArchiveSettings::ARCHIVE_TARGET_CASE => [
        ArchiveTargetCase::NAME => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Case ID'),
          '#default_value' => $settings->archiveTargetCase?->caseId,

          '#states' => [
            'visible' => [
              ':input[name="' . ArchiveSettings::NAME . '][' . ArchiveSettings::ARCHIVE_TARGET_CASE . '][' . ArchiveTargetCase::NAME . '"]' => [
                'value' => ArchiveTarget::CaseID->value,
              ],
            ],
          ],
        ],
      ],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo Validate something?
    parent::validateConfigurationForm($form, $form_state);
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
    $settings = $this->settingsService->getArchiveSettings();

    $build = [
      'info' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ],
    ];

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
