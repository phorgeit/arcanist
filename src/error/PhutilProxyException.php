<?php

/**
 * Prior to PHP 5.3, PHP does not support nested exceptions; this class provides
 * limited support for nested exceptions. Use methods on
 * @{class:PhutilErrorHandler} to unnest exceptions in a forward-compatible way.
 *
 * @concrete-extensible
 *
 * @deprecated Use the exception constructor argument $previous directly.
 */
class PhutilProxyException extends Exception {

  public function __construct($message, $previous, $code = 0) {
    parent::__construct($message, $code, $previous);
  }

  public function getPreviousException() {
    return $this->getPrevious();
  }

}
