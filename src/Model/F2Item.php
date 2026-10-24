<?php

namespace Drupal\os2forms_f2\Model;

use Drupal\os2forms_f2\Model\F2Item\Type;

/**
 * F2 item.
 */
final class F2Item {

  /**
   * Constructor.
   */
  public function __construct(
    public readonly string $webformId,
    public readonly string $webformHandlerId,
    public readonly int $webformSubmissionId,
    public readonly Type $type,
    public readonly array $data,
    public ?int $createdAt = NULL,
    public ?int $updatedAt = NULL,
  ) {
  }

}
