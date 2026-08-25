<?php

namespace Drupal\os2forms_f2;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\os2forms_f2\Plugin\WebformHandler\WebformHandlerF2;
use Drupal\os2forms_f2\Settings\ArchiveSettings;
use Drupal\os2forms_f2\Settings\F2ApiSettings;
use Drupal\os2forms_f2\Settings\GeneralSettings;
use Drupal\os2forms_f2\Settings\HandlerSettings;

/**
 * Settings for module and handler.
 */
class Settings {
  const string CONFIG_NAME = 'os2forms_f2.settings';

  /**
   * The config.
   */
  private readonly ImmutableConfig $config;

  public function __construct(
    ConfigFactoryInterface $configFactory,
  ) {
    $this->config = $configFactory->get(self::CONFIG_NAME);
  }

  /**
   * Get F2 API settings.
   */
  public function getF2ApiSettings(): F2ApiSettings {
    return new F2ApiSettings($this->getValue(F2ApiSettings::NAME));
  }

  /**
   * Get general settings.
   */
  public function getGeneralSettings(): GeneralSettings {
    return new GeneralSettings($this->getValue(GeneralSettings::NAME));
  }

  /**
   * Get handler settings.
   */
  public function getArchiveSettings(array $values = []): ArchiveSettings {
    return (new ArchiveSettings($this->getValue(ArchiveSettings::NAME)))
      ->apply($values);
  }

  /**
   * Get handler settings.
   *
   * The settings are the global settings with handler specific settings on top.
   */
  public function getHandlerSettings(WebformHandlerF2 $handler): HandlerSettings {
    $handlerSettings = $handler->getSettings();

    return new HandlerSettings([
      HandlerSettings::HANDLER_ID => $handler->gethandlerId(),
      HandlerSettings::ARCHIVE => $this->getArchiveSettings($handlerSettings[ArchiveSettings::NAME] ?? []),
    ]);
  }

  /**
   * Get settings value.
   *
   * @return array<string, mixed>
   *   The settings values.
   */
  private function getValue(string $section): array {
    $values = $this->config->get($section);

    return is_array($values) ? $values : [];
  }

}
