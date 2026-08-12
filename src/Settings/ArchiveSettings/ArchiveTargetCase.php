<?php

namespace Drupal\os2forms_f2\Settings\ArchiveSettings;

use Drupal\os2forms_f2\Settings\AbstractSettings;
use Drupal\os2forms_f2\Settings\ArchiveSettings;

/**
 * Settings for ArchiveTargetCase.
 */
final class ArchiveTargetCase extends AbstractSettings {
  const string NAME = ArchiveSettings::ARCHIVE_TARGET_CASE;

  const string CASE_ID = 'case_id';
  public ?int $caseId = NULL;

}
