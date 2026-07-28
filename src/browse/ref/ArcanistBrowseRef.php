<?php

final class ArcanistBrowseRef
  extends ArcanistRef {

  const HARDPOINT_URIS = 'uris';
  const HARDPOINT_COMMITREFS = 'commitRefs';

  /**
   * @var string
   */
  private $token;
  private $types = array();

  /**
   * Branch name (e.g. 'main')
   * @var string|null
   */
  private $branch;

  /**
   * Branch support
   * This is useful to avoid ambiguity, especially when
   * the branch attribute is null.
   * @var bool|null True:  supported.
   *                False: unsupported.
   *                Null:  unknown support.
   */
  private $branchSupported;

  public function getRefDisplayName() {
    return pht('Browse Query "%s"', $this->getToken());
  }

  protected function newHardpoints() {
    return array(
      $this->newVectorHardpoint(self::HARDPOINT_COMMITREFS),
      $this->newVectorHardpoint(self::HARDPOINT_URIS),
    );
  }

  /**
   * @param string $token
   * @return $this
   */
  public function setToken($token) {
    $this->token = $token;
    return $this;
  }

  /**
   * @return string
   */
  public function getToken() {
    return $this->token;
  }

  public function setTypes(array $types) {
    $this->types = $types;
    return $this;
  }

  public function getTypes() {
    return $this->types;
  }

  public function hasType($type) {
    $map = $this->getTypes();
    $map = array_fuse($map);
    return isset($map[$type]);
  }

  public function isUntyped() {
    return !$this->types;
  }

  /**
   * Set a branch name.
   * See also setBranchSupported() and use it accordingly.
   * @param string|null $branch Branch name like 'main' or 'master'
   * @return self
   */
   public function setBranch($branch) {
     $this->branch = $branch;
     return $this;
   }

  /**
   * Get the branch name.
   * You may want to also check getBranchSupported() first.
   * @return string|null Branch name like 'main' or 'master'
   */
  public function getBranch() {
    return $this->branch;
  }

  /**
   * Set if the server really supports branches, or not.
   * Generally we assume yes. In Subversion it's a nope.
   * @param bool|null $branch_supported
   * @return self
   */
  public function setBranchSupported($branch_supported) {
    $this->branchSupported = $branch_supported;
    return $this;
  }

  /**
   * Check if the server really supports branches, or not.
   * Generally we assume yes. In Subversion it's a nope.
   * @return bool|null If the info is unknown you may get null.
   */
  public function getBranchSupported() {
    return $this->branchSupported;
  }

  /**
   * Get URI references, removing duplicate destinations.
   * For example, when among the references there is one about
   * a local git commit, and one about a remote git commit object,
   * it just returns one of these.
   * @return array<string, ArcanistBrowseURIRef> Array of references
   *                                             indexed by URI.
   */
  public function getURIs() {
    return mpull($this->getAllURIs(), null, 'getURI');
  }

  /**
   * Get all URI references.
   * This may return refs about the same destination URI.
   * To avoid duplicated destinations, see @{method:getURIs}
   * instead.
   * @return array<ArcanistBrowseURIRef>
   */
  private function getAllURIs() {
    return $this->getHardpoint(self::HARDPOINT_URIS);
  }

  public function getCommitRefs() {
    return $this->getHardpoint(self::HARDPOINT_COMMITREFS);
  }

}
