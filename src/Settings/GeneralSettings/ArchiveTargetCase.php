<?php

namespace Drupal\os2forms_f2\Settings\GeneralSettings;

use Drupal\os2forms_f2\Settings\AbstractSettings;
use Drupal\os2forms_f2\Settings\ArchiveSettings;
use ItkDev\F2ApiClient\Model\Matter\Type as MatterType;
use ItkDev\F2ApiClient\Model\Matter\AccessLevel as MatterAccessLevel;
use ItkDev\F2ApiClient\Model\Matter\Status as MatterStatus;

/**
 * Settings for ArchiveTargetCase.
 */
final class ArchiveTargetCase extends AbstractSettings {
  protected static array $enumProperties = [
    self::MATTER_TYPE_DEFAULT_VALUE => MatterType::class,
    self::MATTER_STATUS_DEFAULT_VALUE => MatterStatus::class,
    self::MATTER_ACCESS_LEVEL_DEFAULT_VALUE => MatterAccessLevel::class,
  ];

  const string NAME = ArchiveSettings::ARCHIVE_TARGET_CASE;

  const string MATTER_TYPE_DEFAULT_VALUE = 'matter_type_default_value';
  public ?MatterType $matterTypeDefaultValue = MatterType::Internal;

  const string MATTER_TYPE_ALLOW_OVERRIDE = 'matter_type_allow_override';
  public bool $matterTypeAllowOverride = FALSE;

  const string MATTER_STATUS_DEFAULT_VALUE = 'matter_status_default_value';
  public ?MatterStatus $matterStatusDefaultValue = MatterStatus::InProgress;

  const string MATTER_STATUS_ALLOW_OVERRIDE = 'matter_status_allow_override';
  public bool $matterStatusAllowOverride = FALSE;

  const string MATTER_ACCESS_LEVEL_DEFAULT_VALUE = 'matter_access_level_default_value';
  public ?MatterAccessLevel $matterAccessLevelDefaultValue = MatterAccessLevel::All;

  const string MATTER_ACCESS_LEVEL_ALLOW_OVERRIDE = 'matter_access_level_allow_override';
  public bool $matterAccessLevelAllowOverride = FALSE;

  const string MATTER_ARCHIVED_DEFAULT_VALUE = 'matter_archived_default_value';
  public ?bool $matterArchivedDefaultValue = TRUE;

  const string MATTER_ARCHIVED_ALLOW_OVERRIDE = 'matter_archived_allow_override';
  public bool $matterArchivedAllowOverride = FALSE;

}
