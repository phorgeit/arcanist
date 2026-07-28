<?php

final class ArcanistBrowseURIRef
  extends ArcanistRef {

  /**
   * @var string
   */
  private $uri;

  /**
   * @var string
   */
  private $type;

  /**
   * @return string
   */
  public function getRefDisplayName() {
    return pht('Browse URI "%s"', $this->getURI());
  }

  /**
   * @param string $uri
   * @return $this
   */
  public function setURI($uri) {
    $this->uri = $uri;
    return $this;
  }

  /**
   * @return string
   */
  public function getURI() {
    return $this->uri;
  }

  /**
   * @param string $type
   * @return $this
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * @return string
   */
  public function getType() {
    return $this->type;
  }

}
