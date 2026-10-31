<?php

declare(strict_types=1);

namespace Drupal\os2forms_test_playwright;

use Drupal\Core\State\StateInterface;
use Drupal\os2forms_test_playwright\Exception\CassetteException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use VCR\Event\BeforeRecordEvent;
use VCR\VCR;
use VCR\VCREvents;

/**
 * VCR operator.
 */
final class VcrOperator implements EventSubscriberInterface {
  private const string CASSETTE = 'os2forms_test_playwright.vcr_operator.cassette';
  private const string CONFIGURATION = 'os2forms_test_playwright.vcr_operator.configuration';
  private const string CONFIGURATION_CASSETTE_PATH = 'cassette_path';

  public function __construct(
    private readonly StateInterface $state,
    private readonly RequestStack $requestStack,
  ) {
  }

  /**
   * Configure VCR.
   *
   * @param array<string, mixed> $configuration
   *   The configuration.
   */
  public function configure(array $configuration): array {
    $conf = $this->state->get(self::CONFIGURATION);
    if (!is_array($conf)) {
      $conf = [];
    }

    foreach ($configuration as $name => $value) {
      switch ($name) {
        case self::CONFIGURATION_CASSETTE_PATH:
          $conf[$name] = $value;
          break;

        default:
          throw new CassetteException(sprintf('Unknown configuration name: %s', $name));
      }
    }

    $this->state->set(self::CONFIGURATION, $conf);

    return $this->validateConfiguration();
  }

  /**
   * Insert cassette.
   */
  public function insertCassette(string $cassette): string {
    if (!preg_match('/^[a-z][a-z0-9_-]+$/', $cassette)) {
      throw new CassetteException('Invalid cassette (must match /^[a-z][a-z0-9_-]+$/)');
    }

    $insertedCassette = $this->getInsertedCassette();
    if (NULL !== $insertedCassette) {
      throw new CassetteException('Cassette already Inserted');
    }
    $this->state->set(self::CASSETTE, $cassette);

    return $this->getInsertedCassette();
  }

  /**
   * Eject cassette.
   */
  public function ejectCassette(): ?string {
    $cassette = $this->state->get(self::CASSETTE);
    $this->state->delete(self::CASSETTE);

    return $cassette;
  }

  /**
   * Get inserted cassette.
   */
  private function getInsertedCassette(): ?string {
    return $this->state->get(self::CASSETTE);
  }

  /**
   * Start recording of a cassette has been inserted.
   */
  private function recordIfCassetteInserted(): void {
    $request = $this->requestStack->getCurrentRequest();
    if (str_starts_with($request->getRequestUri(), '/os2forms-test-playwright/vcr/')) {
      return;
    }
    $cassette = $this->getInsertedCassette();
    if (NULL !== $cassette) {
      $configuration = $this->validateConfiguration();
      VCR::configure()
        ->setCassettePath($configuration[self::CONFIGURATION_CASSETTE_PATH]);
      VCR::turnOn();
      VCR::insertCassette($cassette);
      $this->addRedaction();
    }
  }

  /**
   * Add response redaction.
   */
  private function addRedaction(): void {
    // Always redact authorization and host headers
    // https://php-vcr.github.io/php-vcr/howto/filter-sensitive-data#redact-a-header
    VCR::getEventDispatcher()->addListener(
      VCREvents::VCR_BEFORE_RECORD,
      function (BeforeRecordEvent $event) {
        $request = $event->getRequest();
        if ($request->hasHeader('Authorization')) {
          $request->setHeader('Authorization', 'REDACTED');
        }
        if ($request->hasHeader('Host')) {
          $request->setHeader('Host', 'REDACTED');
        }
      }
    );
  }

  /**
   * Kernel request listener.
   */
  public function onKernelRequest(RequestEvent $event): void {
    $this->recordIfCassetteInserted();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
    ];
  }

  /**
   *
   */
  private function validateConfiguration(): array {
    $configuration = $this->state->get(self::CONFIGURATION);
    $value = $configuration[self::CONFIGURATION_CASSETTE_PATH];
    if (NULL === $value) {
      throw new CassetteException(sprintf('Configuration "%s" not set', self::CONFIGURATION_CASSETTE_PATH));
    }

    return $configuration;
  }

}
