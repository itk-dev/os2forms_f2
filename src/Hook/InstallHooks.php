<?php

namespace Drupal\os2forms_f2\Hook;

/**
 * Install hook implementations.
 */
final class InstallHooks {
  const string TABLE_DATA = 'os2forms_f2_data';

  /**
   * Implements hook_schema().
   */
  public function schema(): array {
    $schema[self::TABLE_DATA] = [
      'fields' => [
        'id' => [
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Unique ID.',
        ],
        'webform_id' => [
          'description' => 'Webform ID.',
          'type' => 'varchar',
          'length' => '256',
        ],
        'webform_handler_id' => [
          'description' => 'Webform handler ID.',
          'type' => 'varchar',
          'length' => '256',
        ],
        'webform_submission_id' => [
          'description' => 'Webform submission ID. References {webform_submissions}.sid.',
          'type' => 'int',
          'size' => 'big',
          'unsigned' => TRUE,
        ],
        'created_at' => [
          'description' => 'The Unix timestamp when the item was created.',
          'type' => 'int',
          'size' => 'big',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'updated_at' => [
          'description' => 'The Unix timestamp when the item was updated.',
          'type' => 'int',
          'size' => 'big',
          'unsigned' => TRUE,
        ],
        'data' => [
          'description' => 'The data (JSON serialized).',
          'type' => 'text',
          'size' => 'big',
          'not null' => TRUE,
        ],
      ],
      'primary key' => [
        'id',
      ],
      'indexes' => [
        'webform_submission' => ['webform_id', 'webform_handler_id', 'webform_submission_id'],
      ],
    ];

    return $schema;
  }

}
