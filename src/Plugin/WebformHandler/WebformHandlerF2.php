<?php

namespace Drupal\os2forms_f2\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\os2forms_f2\Exception\F2Exception;
use Drupal\os2forms_f2\Exception\F2NonUniqueCaseException;
use Drupal\os2forms_f2\Helper\F2Helper;
use Drupal\os2forms_f2\Helper\WebformHelperF2;
use Drupal\os2forms_f2\Settings;
use Drupal\os2forms_f2\Settings\ArchiveSettings;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTarget;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTargetCase;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTargetMatter;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformSubmissionInterface;
use ItkDev\F2ApiClient\Model\Matter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * F2 Webform Handler.
 *
 * @WebformHandler(
 *   id = "os2forms_f2_f2",
 *   label = @Translation("F2", context="os2forms_f2"),
 *   category = @Translation("Web services", context="os2forms_f2"),
 *   description = @Translation("Sends webform submission to F2.", context="os2forms_f2"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class WebformHandlerF2 extends WebformHandlerBase {
  use StringTranslationTrait;
  use WebformHandlerAdditionalStatesTrait;

  /**
   * Webform handler ID.
   *
   * This ID should match the one assigned in the @WebformHandlerBase
   * incantation.
   */
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
   *
   * @phpstan-return array<string, mixed>
   */
  #[\Override]
  public function defaultConfiguration() {
    return $this->additionalStatesDefaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getOffCanvasWidth(): string {
    return WebformDialogHelper::DIALOG_NONE;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array
  {
    $settings = $this->settingsService->getArchiveSettings((array)($this->getSetting(ArchiveSettings::NAME)));

    // We have to wrap all handler setting fields in a container to show them on
    // the "General" page only.
    $form[self::ID] = [
      '#type' => 'fieldset',
      '#title' => $this->t('F2 archive settings', options: ['context' => 'os2forms_f2']),
      ArchiveSettings::NAME => [
        ArchiveSettings::ATTACHMENT_ELEMENT => [
          '#type' => 'select',
          '#required' => TRUE,
          '#title' => $this->t('Attachment element', options: ['context' => 'os2forms_f2']),
          '#default_value' => $settings->attachmentElement,
          '#options' => $this->getAttachmentElements(),
        ],

        ArchiveSettings::DOCUMENT_TITLE => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Document title', options: ['context' => 'os2forms_f2']),
          '#description' => $this->t('The title of the document. Tokens can be used in the title, e.g. <code>[webform_submission:label]</code>.', options: ['context' => 'os2forms_f2']),
          '#default_value' => $settings->documentTitle,
        ],

        ArchiveSettings::ARCHIVE_TARGET => [
          '#type' => 'select',
          '#required' => TRUE,
          '#title' => $this->t('Archive target', options: ['context' => 'os2forms_f2']),
          '#default_value' => $settings->archiveTarget?->value,
          '#options' => [
            ArchiveTarget::Case->value => $this->t('Case', options: ['context' => 'os2forms_f2']),
            ArchiveTarget::Matter->value => $this->t('Matter', options: ['context' => 'os2forms_f2']),
          ],
        ],

        ArchiveTargetCase::NAME => [
          '#type' => 'fieldset',
          '#title' => $this->t('Case', options: ['context' => 'os2forms_f2']),
          '#states' => [
            'visible' => [
              ':input[name="settings[' . implode('][', [
                self::ID,
                ArchiveSettings::NAME,
                ArchiveSettings::ARCHIVE_TARGET,
              ]) . ']"]' => [
                'value' => ArchiveTarget::Case->value,
              ],
            ],
          ],

          ArchiveTargetCase::CASE_IDENTIFIER => [
            '#type' => 'textfield',
            '#title' => $this->t('Case identifier', options: ['context' => 'os2forms_f2']),
            '#description' => $this->t('Case number or ID that uniquely identifies a single case.', options: ['context' => 'os2forms_f2']),
            '#default_value' => $settings->archiveTargetCase?->caseIdentifier,

            '#states' => [
              'required' => [
                ':input[name="settings[' . implode('][', [
                  self::ID,
                  ArchiveSettings::NAME,
                  ArchiveSettings::ARCHIVE_TARGET,
                ]) . ']"]' => [
                  'value' => ArchiveTarget::Case->value,
                ],
              ],
            ],
          ],

          ArchiveTargetCase::MATTER_TITLE => [
            '#type' => 'textfield',
            '#title' => $this->t('Matter title', options: ['context' => 'os2forms_f2']),
            '#description' => $this->t('The title of the matter that will be created for the document. Tokens can be used in the title, e.g. <code>[webform_submission:label]</code>.', options: ['context' => 'os2forms_f2']),
            '#default_value' => $settings->archiveTargetCase?->matterTitle,

            '#states' => [
              'required' => [
                ':input[name="settings[' . implode('][', [
                  self::ID,
                  ArchiveSettings::NAME,
                  ArchiveSettings::ARCHIVE_TARGET,
                ]) . ']"]' => [
                  'value' => ArchiveTarget::Case->value,
                ],
              ],
            ],
          ],

          ArchiveTargetCase::MATTER_TYPE => [
            '#type' => 'select',
            '#options' => [
              Matter::TYPE_INBOUND => $this->t('Inbound', options: ['context' => 'os2forms_f2']),
              Matter::TYPE_INTERNAL => $this->t('Internal', options: ['context' => 'os2forms_f2']),
              Matter::TYPE_OUTBOUND => $this->t('Outbound', options: ['context' => 'os2forms_f2']),
            ],
            '#title' => $this->t('Matter type', options: ['context' => 'os2forms_f2']),
            '#description' => $this->t('The type of the matter that will be created for the document.', options: ['context' => 'os2forms_f2']),
            '#default_value' => $settings->archiveTargetCase?->matterType,
            '#required' => TRUE,
          ],

        ],

        ArchiveTargetMatter::NAME => [
          '#type' => 'fieldset',
          '#title' => $this->t('Matter', options: ['context' => 'os2forms_f2']),
          '#states' => [
            'visible' => [
              ':input[name="settings[' . implode('][', [
                self::ID,
                ArchiveSettings::NAME,
                ArchiveSettings::ARCHIVE_TARGET,
              ]) . ']"]' => [
                'value' => ArchiveTarget::Matter->value,
              ],
            ],
          ],

          ArchiveTargetMatter::MATTER_IDENTIFIER => [
            '#type' => 'textfield',
            '#title' => $this->t('Matter identifier', options: ['context' => 'os2forms_f2']),
            '#description' => $this->t('Matter number or ID that uniquely identifies a single matter.', options: ['context' => 'os2forms_f2']),
            '#default_value' => $settings->archiveTargetMatter?->matterIdentifier,

            '#states' => [
              'required' => [
                ':input[name="settings[' . implode('][', [
                  self::ID,
                  ArchiveSettings::NAME,
                  ArchiveSettings::ARCHIVE_TARGET,
                ]) . ']"]' => [
                  'value' => ArchiveTarget::Matter->value,
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    // Show some details in the archive target.
    switch ($settings->archiveTarget) {
      case ArchiveTarget::Case:
        $caseIdentifier = $settings->archiveTargetCase?->caseIdentifier;
        if (NULL !== $caseIdentifier) {
          try {
            $case = $this->f2->fetchCaseByIdentifier($caseIdentifier);

            $details = [
              '#markup' => (string)$case,
            ];
          } catch (\Throwable $t) {
            $details = [
              '#theme' => 'status_messages',
              '#message_list' => [
                'error' => [
                  $t->getMessage(),
                ],
              ],
            ];
          }

          $form[self::ID][ArchiveSettings::NAME][ArchiveTargetCase::NAME]['details'] = [
            '#type' => 'details',
            '#attributes' => ['id' => 'archive-details'],
            '#open' => TRUE,
            '#title' => $this->t('Case details', options: ['context' => 'os2forms_f2']),
            ] + $details;
        }
        break;

      case ArchiveTarget::Matter:
        $matterIdentifier = $settings->archiveTargetMatter?->matterIdentifier;
        if (NULL !== $matterIdentifier) {
          try {
            $matter = $this->f2->fetchMatterByIdentifier($matterIdentifier);
            $details = [
              '#markup' => (string)$matter,
            ];
          } catch (\Throwable $t) {
            $details = [
              '#theme' => 'status_messages',
              '#message_list' => [
                'error' => [
                  $t->getMessage(),
                ],
              ],
            ];
          }

          $form[self::ID][ArchiveSettings::NAME][ArchiveTargetMatter::NAME]['details'] = [
              '#type' => 'details',
              '#attributes' => ['id' => 'archive-details'],
              '#open' => TRUE,
              '#title' => $this->t('Matter details', options: ['context' => 'os2forms_f2']),
            ]+ $details;
        }
        break;

    }

    $this->additionalStatesBuildConfigurationForm($form, $form_state);

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void
  {
    parent::validateConfigurationForm($form, $form_state);

    $setError = static fn(string|array $path, TranslatableMarkup $message) => $form_state->setErrorByName(implode('][', (array)$path), $message);

    $targetKey = [self::ID, ArchiveSettings::NAME, ArchiveSettings::ARCHIVE_TARGET];
    $target = $form_state->getValue($targetKey);
    try {
      $target = ArchiveTarget::from($target);
      switch ($target) {
        case ArchiveTarget::Case:
          $key = [self::ID, ArchiveSettings::NAME, ArchiveTargetCase::NAME, ArchiveTargetCase::CASE_IDENTIFIER];
          $caseIdentifier = trim((string)$form_state->getValue($key));
          if ('' === $caseIdentifier) {
            $setError($key, t('Missing case identifier.', options: ['context' => 'os2forms_f2']));
          } else {
            try {
              $this->f2->fetchCaseByIdentifier($caseIdentifier);
            } catch (F2NonUniqueCaseException) {
              $setError($key, t('Identifier "@case_identifier" does not uniquely identify a case.', [
                '@case_identifier' => $caseIdentifier,
              ], options: ['context' => 'os2forms_f2']));
            } catch (\Throwable $throwable) {
              $setError($key, t('Cannot get case by identifier "@case_identifier" (@message).', [
                '@case_identifier' => $caseIdentifier,
                '@message' => $throwable->getMessage(),
              ], options: ['context' => 'os2forms_f2']));
            }
          }
          break;

        case ArchiveTarget::Matter:
          $key = [self::ID, ArchiveSettings::NAME, ArchiveTargetMatter::NAME, ArchiveTargetMatter::MATTER_IDENTIFIER];
          $matterIdentifier = trim((string)$form_state->getValue($key));
          if ('' === $matterIdentifier) {
            $setError($key, t('Missing matter identifier.', options: ['context' => 'os2forms_f2']));
          } else {
            try {
              $this->f2->fetchMatterByIdentifier($matterIdentifier);
            } catch (\Throwable $throwable) {
              $setError($key, t('Cannot get matter by identifier "@matter_identifier" (@message).', [
                '@matter_identifier' => $matterIdentifier,
                '@message' => $throwable->getMessage(),
              ], options: ['context' => 'os2forms_f2']));
            }
          }
          break;
      }
    } catch (\ValueError) {
      $setError($targetKey, $this->t('Invalid archive target: @target', ['@target' => $target], options: ['context' => 'os2forms_f2']));
    }
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    foreach ([
      ArchiveSettings::NAME,
    ] as $name) {
      $this->configuration[$name] = $form_state->getValue([self::ID, $name]);
    }

    $this->additionalStatesSubmitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    if (!$this->additionalStatesRunOnPostSave($webform_submission)) {
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

    $summary = [];
    $linkToDetails = false;

    switch ($settings->archive?->archiveTarget) {
      case ArchiveTarget::Case:
        $caseIdentifier = $settings->archive->archiveTargetCase?->caseIdentifier;
        if ($caseIdentifier) {
          $summary[ArchiveTargetCase::NAME] = [
            '#markup' => $this->t('Archive on case "@case_identifier".', ['@case_identifier' => $caseIdentifier], options: ['context' => 'os2forms_f2']),
          ];
        }
        $linkToDetails = true;
        break;

      case ArchiveTarget::Matter:
        $matterIdentifier = $settings->archive->archiveTargetMatter?->matterIdentifier;
        if ($matterIdentifier) {
          $summary[ArchiveTargetMatter::NAME] = [
            '#markup' => $this->t('Archive on matter "@matter_identifier".', ['@matter_identifier' => $matterIdentifier], options: ['context' => 'os2forms_f2']),
          ];
        }
        $linkToDetails = true;
        break;
    }


    $build = [];
    if ($summary) {
      $build['summary'] = [
          '#type' => 'container',
        ] + $summary;
      if ($linkToDetails) {
        $build['see_details'] = [
          '#type' => 'container',
          '#markup' => ' '.$this->t('<a href=":edit_url">Edit</a> to see details.', [
              ':edit_url' => Url::fromRoute('entity.webform.handler.edit_form', [
                'webform' => $this->getWebform()->id(),
                'webform_handler' => $this->getHandlerId(),
              ])->toString(true)->getGeneratedUrl(),
            ], options: ['context' => 'os2forms_f2']),
          '#weight' => 999,
        ];
      }
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
      'os2forms_attachment',
    ];
    $elements = array_filter(
      $elements,
      static fn(array $element) => in_array($element['#type'], $elementTypes, TRUE)
    );

    return array_map(static fn(array $element) => $element['#title'], $elements);
  }

}
