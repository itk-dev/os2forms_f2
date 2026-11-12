<?php

namespace Drupal\os2forms_f2\Settings;

use Drupal\Core\Site\Settings as DrupalSettings;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTarget;
use Drupal\os2forms_f2\Settings\GeneralSettings\ArchiveTargetCase;
use ItkDev\F2ApiClient\Model\Matter\Type as MatterType;

/**
 * General module settings.
 */
final class GeneralSettings extends AbstractSettings {
  const string NAME = 'general';

  protected static array $enumProperties = [
    self::MATTER_TYPE_DEFAULT_VALUE => MatterType::class,
  ];

  protected static array $settingsProperties = [
    self::ARCHIVE_TARGET_CASE => ArchiveTargetCase::class,
  ];

  const string QUEUE = 'queue';
  public ?string $queue = NULL;

  const string ARCHIVE_TARGETS = 'archive_targets';
  /**
   * The (enabled) archive target.
   *
   * @var array<string, string>
   */
  public array $archiveTargets = [
    ArchiveTarget::Case->value => ArchiveTarget::Case->value,
  ];

  const string MATTER_FILE_DOCUMENT_TITLE_DEFAULT_VALUE = 'matter_file_document_title_default_value';
  public string $matterFileDocumentTitleDefaultValue = '[file:name]';

  const string MATTER_TYPE_DEFAULT_VALUE = 'matter_type_default_value';
  public MatterType $matterTypeDefaultValue = MatterType::Internal;

  const string ARCHIVE_TARGET_CASE = 'archive_target_case';
  public ?ArchiveTargetCase $archiveTargetCase = NULL;

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

  /**
   * Get enabled archive targets.
   */
  public function getEnabledArchiveTargets(): array {
    return array_filter($this->archiveTargets);
  }

}
