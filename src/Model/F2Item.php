<?php

namespace Drupal\os2forms_f2\Model;

use Drupal\os2forms_f2\Model\F2Item\Type;

/**
 * F2 item.
 *
 * These are stored in and loaded from the database by the F2ItemRepository.
 *
 * @see \Drupal\os2forms_f2\Repository\F2ItemRepository
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
    public readonly ?string $identifier,
    public readonly array $data,
    public ?int $createdAt = NULL,
    public ?int $updatedAt = NULL,
  ) {
  }

}
