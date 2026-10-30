<?php

namespace Drupal\os2forms_f2\Helper;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\os2forms_f2\Exception\F2Exception;
use Drupal\os2forms_f2\Exception\F2NonUniqueCaseException;
use Drupal\os2forms_f2\Exception\F2NonUniqueMatterException;
use Drupal\os2forms_f2\Settings;
use ItkDev\F2ApiClient\ApiClient;
use ItkDev\F2ApiClient\Model\CaseFile;
use ItkDev\F2ApiClient\Model\Matter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Helper for talking to F2.
 */
class F2Helper {

  public function __construct(
    private readonly Settings $settings,
    #[Autowire(service: 'drupal_psr6_cache.cache_item_pool')]
    private readonly CacheItemPoolInterface $cacheItemPool,
    #[Autowire(service: 'logger.channel.os2forms_f2')]
    private readonly LoggerChannelInterface $logger,
  ) {
  }

  /**
   * The F2 API client.
   */
  private ?ApiClient $apiClient = NULL;

  /**
   * Get API client.
   */
  public function client(): ApiClient {
    if (NULL === $this->apiClient) {
      $settings = $this->settings->getF2ApiSettings();
      $this->apiClient = new ApiClient([
        'api_uri' => $settings->uri,
        'api_username' => $settings->username,
        'api_secret' => $settings->secret,
        'f2_username' => $settings->f2Username,
        'cache_item_pool' => $this->cacheItemPool,
        'log_level' => $settings->logLevel,
      ]);
      $this->apiClient->setLogger($this->logger);
    }

    return $this->apiClient;
  }

  /**
   * Ping API.
   */
  public function pingApi(): void {
    $this->client()->matterSearch('ping');
  }

  /**
   * Fetch case by ID or number (by searching and expecting one result).
   *
   * @throws \Drupal\os2forms_f2\Exception\F2NonUniqueCaseException
   *   If search does not return a single result.
   */
  public function fetchCaseByIdentifier(string $identifier): CaseFile {
    $client = $this->client();

    // First, we try to get a case by number (which is a string)
    try {
      return $client->caseByCaseNumber($identifier);
    }
    catch (\Throwable) {
      // Ignore any errors.
    }

    // Search for case.
    $cases = $client->caseSearch($identifier);
    if (1 !== count($cases)) {
      throw new F2NonUniqueCaseException($identifier);
    }
    $case = reset($cases);

    return $client->caseById($this->getId($case->id));
  }

  /**
   * Fetch matter by ID or number.
   */
  public function fetchMatterByIdentifier(string $identifier): Matter {
    $client = $this->client();

    // First, we try to get a matter by number (which is an integer)
    if (filter_var($identifier, FILTER_VALIDATE_INT)) {
      try {
        return $client->matterByMatterNumber($identifier);
      }
      catch (\Throwable) {
        // Ignore any errors.
      }
    }

    // Search for matter.
    $matters = $client->matterSearch($identifier);
    if (1 !== count($matters)) {
      throw new F2NonUniqueMatterException($identifier);
    }
    $matter = reset($matters);

    return $client->matterById($this->getId($matter->id));
  }

  /**
   * Get integer ID from F2 item URL.
   */
  private function getId(string $url): int {
    if (preg_match('@/cases/(?<id>\d+)$@', $url, $matches)) {
      return (int) $matches['id'];
    }

    throw new F2Exception('Cannot get ID from ' . $url);
  }

}
