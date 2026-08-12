<?php

namespace Drupal\os2forms_f2\Helper;

use Drupal\os2forms_f2\Settings;
use ItkDev\F2ApiClient\ApiClient;
use ItkDev\F2ApiClient\Model\CaseFile;
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
  ) {
  }

  /**
   *
   */
  public function getCaseById(int $id): CaseFile {
    return $this->client()->caseById($id);
  }

  /**
   * @return CaseFile[]
   */
  public function caseList(string $q, int $count = 10): array {
    return $this->client()->caseSearch($q, $count);
  }

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
    }

    return $this->apiClient;
  }

}
