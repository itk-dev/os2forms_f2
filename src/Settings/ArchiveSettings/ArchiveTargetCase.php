<?php

namespace Drupal\os2forms_f2\Settings\ArchiveSettings;

use Drupal\os2forms_f2\Settings\AbstractSettings;

/**
 * Settings for ArchiveTargetCase.
 */
final class ArchiveTargetCase extends AbstractSettings {
  const string NAME = 'archive_target_case';
  const string CASE_ID = 'case_id';
  public ?string $caseId = NULL;

}
