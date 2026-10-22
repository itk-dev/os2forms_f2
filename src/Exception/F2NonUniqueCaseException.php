<?php

namespace Drupal\os2forms_f2\Exception;

class F2NonUniqueCaseException extends F2Exception {
  public function __construct(private readonly string $identifier, string $message = "", int $code = 0, ?\Throwable $previous = null)
  {
    $message = sprintf('Identifier "%s" does not uniquely identify a case', $this->identifier);
    parent::__construct($message, $code, $previous);
  }

  /**
   * @return string
   */
  public function getIdentifier(): string
  {
    return $this->identifier;
  }
}
