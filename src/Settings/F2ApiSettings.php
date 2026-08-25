<?php

namespace Drupal\os2forms_f2\Settings;

/**
 * F2 API settings.
 */
final class F2ApiSettings extends AbstractSettings {
  const string NAME = 'f2_api';

  const string URI = 'uri';
  public ?string $uri = NULL;

  const string USERNAME = 'username';
  public ?string $username = NULL;

  const string SECRET = 'secret';
  public ?string $secret = NULL;

  const string F2_USERNAME = 'f2_username';
  public ?string $f2Username = NULL;

}
