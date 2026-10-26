<?php

/**
 * @file
 */

declare(strict_types=1);

use DrupalRector\Set\Drupal11SetList;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
  ->withPaths([
    __DIR__ . '/src',
    // __DIR__ . '/tests',
  ])
  ->withSets([
    Drupal11SetList::DRUPAL_114,
  ])
  ->withPhpSets(php83: TRUE)
  ->withTypeCoverageLevel(0);
