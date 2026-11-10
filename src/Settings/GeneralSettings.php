<?php

namespace Drupal\os2forms_f2\Settings;

use Drupal\Core\Site\Settings as DrupalSettings;

/**
 * General module settings.
 */
final class GeneralSettings extends AbstractSettings {
  const string NAME = 'general';

  const string QUEUE = 'queue';
  public ?string $queue = NULL;

  /**
   * Get user manual URL.
   */
  public static function getUserManualUrl(): string {
    $settings = DrupalSettings::get('os2forms_f2');

    return $settings['user_manual']['url'] ?? 'https://itk-dev.github.io/os2forms_f2/user-manual/da/';
  }

  /**
   * Get form builder user manual URL.
   */
  public static function getFormBuilderUserManualUrl(): string {
    $settings = DrupalSettings::get('os2forms_f2');

    return $settings['user_manual']['url_form_builder'] ?? 'https://itk-dev.github.io/os2forms_f2/user-manual/da/';
  }

}
