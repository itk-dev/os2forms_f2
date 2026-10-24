<?php

namespace Drupal\os2forms_f2\Repository;

use Drupal\os2forms_f2\Hook\InstallHooks;
use Drupal\os2forms_f2\Model\F2Item;
use Drupal\os2forms_f2\Model\F2Item\Type;
use Drupal\os2forms_f2\Plugin\WebformHandler\WebformHandlerF2;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Repository for F2 items.
 */
final class F2ItemRepository extends AbstractRepository {
  private const string TABLE = InstallHooks::TABLE_F2_ITEM;

  /**
   * Load F2 items.
   *
   * @param array $conditions
   *   The criteria.
   * @param array<string, string> $orderBy
   *   The order.
   *
   * @return \Drupal\os2forms_f2\Model\F2Item[]
   *   The list of items
   */
  private function loadBy(array $conditions = [], array $orderBy = ['created_at' => 'DESC']): array {
    $query = $this->database
      ->select(self::TABLE, 't')
      ->fields('t');

    foreach ($conditions as $condition) {
      $query->condition(...$condition);
    }

    foreach ($orderBy as $field => $direction) {
      $query->orderBy($field, $direction);
    }

    $statement = $query->execute();
    assert(NULL !== $statement);
    $result = $statement->fetchAll();

    return array_map(
      fn(object $row) => new F2Item(
        webformId: $row->webform_id,
        webformHandlerId: $row->webform_handler_id,
        webformSubmissionId: $row->webform_submission_id,
        type: Type::from($row->type),
        data: $this->unserializeData($row->data),
        createdAt: $row->created_at,
        updatedAt: $row->updated_at,
      ),
      array: $result
    );
  }

  /**
   * Load one item by ...
   */
  public function loadOneBySubmissionAndHandlerAndType(WebformSubmissionInterface $submission, string $handlerId, Type $type): ?F2Item {
    $items = $this->loadBy([
      ['webform_id', $submission->getWebform()->id()],
      ['webform_handler_id', $handlerId],
      ['webform_submission_id', $submission->id()],
      ['type', $type->value],
    ]);

    return 1 === count($items) ? reset($items) : NULL;
  }

  /**
   * Load items for a submission.
   *
   * @return \Drupal\os2forms_f2\Model\F2Item[]
   *   The result.
   */
  public function loadBySubmission(WebformSubmissionInterface $submission): array {
    return $this->loadBy([
      ['webform_submission_id', $submission->id()],
    ]);
  }

  /**
   * Load by ...
   */
  public function loadByWebformAndHandler(WebformInterface $webform, WebformHandlerF2 $handler) {
    return $this->loadBy([
      ['webform_id', $webform->id()],
      ['webform_handler_id', $handler->gethandlerId()],
    ]);
  }

  /**
   * Delete by submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface[] $submissions
   *   The submissions.
   */
  public function deleteBySubmissions(array $submissions): int {
    if (empty($submissions)) {
      return 0;
    }

    try {
      $ids = array_map(static fn(WebformSubmissionInterface $submission) => $submission->id(), $submissions);
      return $this->database->delete(self::TABLE)
        ->condition('webform_submission_id', $ids, 'IN')
        ->execute();
    }
    catch (\Exception $exception) {
      $this->logger->error('Error deleting F2 items for submissions @submissions: @message', [
        '@submissions' => implode(', ', $ids),
        '@message' => $exception->getMessage(),
        'exception' => $exception,
      ]);
    }

    return 0;
  }

  /**
   * Save F2 item.
   */
  public function save(F2Item $item): bool {
    try {
      $now = $this->time->getRequestTime();
      $item->createdAt ??= $now;
      $item->updatedAt = $now;

      $fields = [
        'webform_id' => $item->webformId,
        'webform_handler_id' => $item->webformHandlerId,
        'webform_submission_id' => $item->webformSubmissionId,
        'type' => $item->type->value,
        'data' => $this->serializeData($item->data),
        'created_at' => $item->createdAt,
        'updated_at' => $item->updatedAt,
      ];

      if (0 === count($this->loadBy([
        ['webform_id', $fields['webform_id']],
        ['webform_handler_id', $fields['webform_handler_id']],
        ['webform_submission_id', $fields['webform_submission_id']],
        ['type', $fields['type']],
      ]))) {
        $this->database
          ->insert(self::TABLE)
          ->fields($fields)
          ->execute();
      }
      else {
        $this->database
          ->update(self::TABLE)
          ->condition('webform_id', $fields['webform_id'])
          ->condition('webform_handler_id', $fields['webform_handler_id'])
          ->condition('webform_submission_id', $fields['webform_submission_id'])
          ->condition('type', $fields['type'])
          ->fields($fields)
          ->execute();
      }

      return TRUE;
    }
    catch (\Exception $exception) {
      $this->logger->error('Error saving F2 item: @message', [
        '@message' => $exception->getMessage(),
        'exception' => $exception,
      ]);
    }

    return FALSE;
  }

  /**
   * Serialize data.
   */
  private function serializeData(array $data) {
    return json_encode($data, JSON_THROW_ON_ERROR);
  }

  /**
   * Unserialize data.
   */
  private function unserializeData(string $data): array {
    return (array) json_decode($data, TRUE);
  }

}
