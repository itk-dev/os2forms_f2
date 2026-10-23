<?php

namespace Drupal\os2forms_f2\Exception;

/**
 * F2 non-unique case exception.
 */
class F2NonUniqueCaseException extends F2Exception {

  public function __construct(private readonly string $identifier, string $message = '', int $code = 0, ?\Throwable $previous = NULL) {
    $message = sprintf('Identifier "%s" does not uniquely identify a case', $this->identifier);
    parent::__construct($message, $code, $previous);
  }

  /**
   * Get case identifier.
   */
  public function getIdentifier(): string {
    return $this->identifier;
  }

}
