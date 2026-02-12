<?php

final class PhutilNumber extends Phobject {

  private $value;
  private $decimals = 0;

  public function __construct($value, $decimals = 0) {
    $this->value = $value;
    $this->decimals = $decimals;
  }

  public function getNumber() {
    return $this->value;
  }

  /**
   * @param int $decimals
   */
  public function setDecimals(int $decimals) {
    $this->decimals = $decimals;
    return $this;
  }

  /**
   * @return int
   */
  public function getDecimals() {
    return $this->decimals;
  }

}
