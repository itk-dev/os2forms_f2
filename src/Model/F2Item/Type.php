<?php

namespace Drupal\os2forms_f2\Model\F2Item;

/**
 * F2 item type.
 */
enum Type: string {
  case CaseFile = 'case';
  case Matter = 'matter';
  case Document = 'document';
}
