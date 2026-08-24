<?php

namespace Drupal\os2forms_f2\Plugin\AdvancedQueue\JobType;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\os2forms_f2\Helper\WebformHelperF2;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Job for archiving in F2.
 *
 * @AdvancedQueueJobType(
 *   id = "Drupal\os2forms_f2\Plugin\AdvancedQueue\JobType\F2",
 *   label = @Translation("F2"),
 *   max_retries = 5,
 *   retry_delay = 60,
 * )
 */
final class F2 extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(WebformHelperF2::class)
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    /**
     * The webform helper.
     */
    private readonly WebformHelperF2 $helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job): JobResult {
    return $this->helper->processJob($job);
  }

}
