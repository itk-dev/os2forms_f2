<?php

namespace Drupal\os2forms_f2\Settings\ArchiveSettings;

use Drupal\os2forms_f2\Settings\AbstractSettings;
use Drupal\os2forms_f2\Settings\ArchiveSettings;

/**
 * Settings for ArchiveTargetMatter.
 */
final class ArchiveTargetMatter extends AbstractSettings {
  const string NAME = ArchiveSettings::ARCHIVE_TARGET_MATTER;

  const string MATTER_ID = 'matter_id';
  public ?int $matterId = NULL;

}
