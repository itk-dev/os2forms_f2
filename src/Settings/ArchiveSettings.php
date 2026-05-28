<?php

namespace Drupal\os2forms_f2\Settings;

use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTarget;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTargetCase;

/**
 * Webform archive settings.
 */
final class ArchiveSettings extends AbstractSettings {
  const string NAME = 'archive';

  protected static array $enumProperties = [
    self::ARCHIVE_TARGET => ArchiveTarget::class,
  ];

  protected static array $settingsProperties = [
    self::ARCHIVE_TARGET_CASE => ArchiveTargetCase::class,
  ];

  const string HANDLER_ID = 'handler_id';
  public string $handlerId;

  const string ATTACHMENT_ELEMENT = 'attachment_element';
  public ?string $attachmentElement = NULL;

  const string ARCHIVE_TARGET = 'archive_target';
  public ?ArchiveTarget $archiveTarget = NULL;

  const string ARCHIVE_TARGET_CASE = 'archive_target_case';
  public ?ArchiveTargetCase $archiveTargetCase = NULL;

}
