<?php

namespace Drupal\os2forms_f2\Exception;

/**
 * F2 non-unique matter exception.
 */
class F2NonUniqueMatterException extends F2Exception {

  public function __construct(private readonly string $identifier, string $message = '', int $code = 0, ?\Throwable $previous = NULL) {
    $message = sprintf('Identifier "%s" does not uniquely identify a matter', $this->identifier);
    parent::__construct($message, $code, $previous);
  }

  /**
   * Get matter identifier.
   */
  public function getIdentifier(): string {
    return $this->identifier;
  }

}
