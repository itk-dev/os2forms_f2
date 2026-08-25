<?php

namespace Drupal\os2forms_f2\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Additional states controlling when the handler actually does its job.
 *
 * Usage:
 *
 * @code
 * final class WebformHandlerExample extends WebformHandlerBase {
 *   …
 *   use WebformHandlerAdditionalStatesTrait;
 *
 *   public function defaultConfiguration() {
 *     // Note: Skip the call to NestedArray::mergeDeep if your handler does not
 *     // have any default configuration itself.
 *     $configuration = [
 *       …,
 *     ];
 *
 *     return NestedArray::mergeDeep($configuration, $this->additionalStatesDefaultConfiguration());
 *   }
 *
 *   public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
 *     …
 *
 *     $this->additionalStatesBuildConfigurationForm($form, $form_state);
 *
 *     return $this->setSettingsParents($form);
 *   }
 *
 *   public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
 *     …
 *
 *     $this->additionalStatesSubmitConfigurationForm($form, $form_state);
 *   }
 *
 *   public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
 *     if (!$this->additionalStatesRunOnPostSave($webform_submission)) {
 *       return;
 *     }
 *
 *     …
 *   }
 * }
 * @endcode
 */
trait WebformHandlerAdditionalStatesTrait {
  private const string ADDITIONAL = 'additional';
  private const string STATES = 'states';
  private const string RESULTS_DISABLED = 'results_disabled';

  /**
   * See code example in file DocBlock.
   */
  private function additionalStatesDefaultConfiguration(): array {
    return [
      self::ADDITIONAL => [
        self::STATES => [WebformSubmissionInterface::STATE_COMPLETED],
      ],
    ];
  }

  /**
   * See code example in file DocBlock.
   */
  private function additionalStatesBuildConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // Additional.
    // Lifted from EmailWebformHandler::buildConfigurationForm().
    $resultsDisabled = (bool) $this->getWebform()->getSetting(self::RESULTS_DISABLED);
    $form[self::ADDITIONAL] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additional settings'),
    ];
    // Settings: States.
    $states = (array) ($this->configuration[self::ADDITIONAL][self::STATES] ?? NULL);
    $form[self::ADDITIONAL][self::STATES] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Run handler when …'),
      '#options' => [
        WebformSubmissionInterface::STATE_DRAFT_CREATED => $this->t('<b>draft is created</b>.'),
        WebformSubmissionInterface::STATE_DRAFT_UPDATED => $this->t('<b>draft is updated</b>.'),
        WebformSubmissionInterface::STATE_CONVERTED => $this->t('anonymous <b>submission is converted</b> to authenticated.'),
        WebformSubmissionInterface::STATE_COMPLETED => $this->t('<b>submission is completed</b>.'),
        WebformSubmissionInterface::STATE_UPDATED => $this->t('<b>submission is updated</b>.'),
        WebformSubmissionInterface::STATE_DELETED => $this->t('<b>submission is deleted</b>.'),
        WebformSubmissionInterface::STATE_LOCKED => $this->t('<b>submission is locked</b>.'),
      ],
      '#access' => !$resultsDisabled,
      '#default_value' => $resultsDisabled ? [WebformSubmissionInterface::STATE_COMPLETED] : $states,
    ];
  }

  /**
   * See code example in file DocBlock.
   */
  private function additionalStatesSubmitConfigurationForm(array $form, FormStateInterface $formState): void {
    $additional = $formState->getValue(self::ADDITIONAL);
    // Clean up states.
    $additional[self::STATES] = array_values(array_filter($additional[self::STATES]));
    $this->configuration[self::ADDITIONAL] = $additional;
  }

  /**
   * See code example in file DocBlock.
   */
  private function additionalStatesRunOnPostSave(WebformSubmissionInterface $submission): bool {
    $submissionState = $submission->getWebform()->getSetting(self::RESULTS_DISABLED) ? WebformSubmissionInterface::STATE_COMPLETED : $submission->getState();
    $enabledStates = (array) ($this->configuration[self::ADDITIONAL][self::STATES] ?? NULL);

    return in_array($submissionState, $enabledStates);
  }

}
