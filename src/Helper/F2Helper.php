<?php

namespace Drupal\os2forms_f2\Helper;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\os2forms_f2\Exception\F2Exception;
use Drupal\os2forms_f2\Exception\F2NonUniqueCaseException;
use Drupal\os2forms_f2\Settings;
use ItkDev\F2ApiClient\ApiClient;
use ItkDev\F2ApiClient\Exception\ApiException;
use ItkDev\F2ApiClient\Model\CaseFile;
use ItkDev\F2ApiClient\Model\Matter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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
   * Fetch case by ID or number.
   *
   * @param string $caseIdentifier
   * @return CaseFile
   * @throws F2Exception
   * @throws F2NonUniqueCaseException
   */
  public function fetchCaseByIdentifier(string $caseIdentifier): CaseFile
  {
    $client = $this->client();
    try {
      return $client->caseById((int)$caseIdentifier);
    } catch (\Throwable) {
      $cases = $client->caseSearch($caseIdentifier);
      if (1 !== count($cases)) {
        throw new F2NonUniqueCaseException($caseIdentifier);
      }
      $case = reset($cases);

      return $client->caseById($this->getId($case->id));
    }
  }

  /**
   * Fetch matter by ID or number.
   *
   * @param string $matterIdentifier
   * @return Matter
   * @throws \ItkDev\F2ApiClient\Exception\ApiException
   */
  public function fetchMatterByIdentifier(string $matterIdentifier): Matter
  {
    $client = $this->client();
    try {
      return $client->matterById((int)$matterIdentifier);
    } catch (\Throwable) {
      return $client->matterByMatterNumber($matterIdentifier);
    }
  }

  private function getId(string $url): int
  {
    if (preg_match('@/cases/(?<id>\d+)$@', $url, $matches)) {
      return (int)$matches['id'];
    }

    throw new F2Exception('Cannot get ID from '.$url);
  }


}
