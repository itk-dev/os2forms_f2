<?php

namespace Drupal\os2forms_f2\Settings\ArchiveSettings;

use Drupal\os2forms_f2\Settings\AbstractSettings;
use Drupal\os2forms_f2\Settings\ArchiveSettings;
use ItkDev\F2ApiClient\Model\Matter;

/**
 * Settings for ArchiveTargetCase.
 */
final class ArchiveTargetCase extends AbstractSettings {
  const string NAME = ArchiveSettings::ARCHIVE_TARGET_CASE;

  const string CASE_IDENTIFIER = 'case_identifier';
  public ?string $caseIdentifier = NULL;

  const string MATTER_TITLE = 'matter_title';
  public ?string $matterTitle = NULL;

  const string MATTER_TYPE = 'matter_type';
  public ?string $matterType = Matter::TYPE_INTERNAL;

}
