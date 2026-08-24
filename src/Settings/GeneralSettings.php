<?php

namespace Drupal\os2forms_f2\Settings;

/**
 * General module settings.
 */
final class GeneralSettings extends AbstractSettings {
  const string NAME = 'general';

  const string QUEUE = 'queue';
  public ?string $queue = NULL;

}
