<?php

namespace Drupal\os2forms_f2\Settings\ArchiveSettings;

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
    self::MATTER_TYPE => MatterType::class,
    self::MATTER_STATUS => MatterStatus::class,
    self::MATTER_ACCESS_LEVEL => MatterAccessLevel::class,
  ];

  const string NAME = ArchiveSettings::ARCHIVE_TARGET_CASE;

  const string CASE_IDENTIFIER = 'case_identifier';
  public ?string $caseIdentifier = NULL;

  const string MATTER_TITLE = 'matter_title';
  public ?string $matterTitle = NULL;

  const string MATTER_TYPE = 'matter_type';
  public ?MatterType $matterType = MatterType::Internal;

  const string MATTER_STATUS = 'matter_status';
  public ?MatterStatus $matterStatus = MatterStatus::InProgress;

  const string MATTER_ACCESS_LEVEL = 'matter_access_level';
  public ?MatterAccessLevel $matterAccessLevel = MatterAccessLevel::All;

  const string MATTER_ARCHIVED = 'matter_archived';
  public ?bool $matterArchived = TRUE;

  const string MATTER_RESPONSIBLE_PARTY_IDENTIFIER = 'matter_responsible_party_identifier';
  public ?string $matterResponsiblePartyIdentifier = NULL;

}
