<?php

declare(strict_types=1);

namespace Drupal\os2forms_f2_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Page hooks.
 */
class PageHooks {

  /**
 * Implements hook_page_attachments().
 */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page): void {
    $page['#attached']['library'][] = 'os2forms_f2_test/allselect';
  }

}
