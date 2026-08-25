<?php

namespace Drupal\os2forms_f2\Settings;

/**
 * Webform handler settings.
 */
final class HandlerSettings extends AbstractSettings {
  protected static array $settingsProperties = [
    self::ARCHIVE => ArchiveSettings::class,
  ];

  const string HANDLER_ID = 'handler_id';
  public string $handlerId;

  const string ARCHIVE = 'archive';
  public ?ArchiveSettings $archive = NULL;

}
