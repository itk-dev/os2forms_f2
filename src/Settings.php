<?php

namespace Drupal\os2forms_f2;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\os2forms_f2\Plugin\WebformHandler\WebformHandlerF2;
use Drupal\os2forms_f2\Settings\ArchiveSettings;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTarget;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTargetCase;
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
  public function getHandlerSettings(WebformHandlerF2 $handler, bool $applyGlobals = FALSE): HandlerSettings {
    $handlerSettings = $handler->getSettings();
    $settings = new HandlerSettings([
      HandlerSettings::HANDLER_ID => $handler->gethandlerId(),
      HandlerSettings::ARCHIVE => $this->getArchiveSettings($handlerSettings[ArchiveSettings::NAME] ?? []),
    ]);

    if ($applyGlobals) {
      $generalSettings = $this->getGeneralSettings();
      switch ($handlerSettings['archive']['archive_target'] ?? NULL) {
        case ArchiveTarget::Case->value:
          // Setting values directly does not work (yet), so we build overrides
          // to apply.
          $overrides = [];
          if (!$generalSettings->archiveTargetCase->matterTypeAllowOverride) {
            $overrides[ArchiveTargetCase::MATTER_TYPE] = $generalSettings->archiveTargetCase->matterTypeDefaultValue;
          }
          if (!$generalSettings->archiveTargetCase->matterStatusAllowOverride) {
            $overrides[ArchiveTargetCase::MATTER_STATUS] = $generalSettings->archiveTargetCase->matterStatusDefaultValue;
          }
          if (!$generalSettings->archiveTargetCase->matterAccessLevelAllowOverride) {
            $overrides[ArchiveTargetCase::MATTER_ACCESS_LEVEL] = $generalSettings->archiveTargetCase->matterAccessLevelDefaultValue;
          }
          if (!$generalSettings->archiveTargetCase->matterArchivedAllowOverride) {
            $overrides[ArchiveTargetCase::MATTER_ARCHIVED] = $generalSettings->archiveTargetCase->matterArchivedDefaultValue;
          }
          $settings->archive->archiveTargetCase->apply($overrides);
          break;
      }
    }

    return $settings;
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
