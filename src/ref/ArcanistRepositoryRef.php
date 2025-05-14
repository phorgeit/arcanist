<?php

final class ArcanistRepositoryRef
  extends ArcanistRef {

  private $parameters = array();
  private $phid;
  private $browseURI;

  public function getRefDisplayName() {
    return pht('Remote Repository');
  }

  public function setPHID($phid) {
    $this->phid = $phid;
    return $this;
  }

  public function getPHID() {
    return $this->phid;
  }

  public function setBrowseURI($browse_uri) {
    $this->browseURI = $browse_uri;
    return $this;
  }

  public static function newFromConduit(array $map) {
    $ref = new self();
    $ref->parameters = $map;

    $ref->phid = $map['phid'];

    return $ref;
  }

  public function getURIs() {
    $uris = idxv($this->parameters, array('attachments', 'uris', 'uris'));

    if (!$uris) {
      return array();
    }

    $results = array();
    foreach ($uris as $uri) {
      $effective_uri = idxv($uri, array('fields', 'uri', 'effective'));
      if ($effective_uri !== null) {
        $results[] = $effective_uri;
      }
    }

    return $results;
  }

  public function getDisplayName() {
    return idxv($this->parameters, array('fields', 'name'));
  }

  public function newBrowseURI(array $params) {
    PhutilTypeSpec::checkMap(
      $params,
      array(
        // Path to the file or directory.
        'path' => 'optional string|null',
        // Destination branch.
        // When the branch is null, we guess the default (e.g. 'master' in git).
        'branch' => 'optional string|null',
        // Branch support capability.
        // If this is false, you are probably using svn.
        'branchSupported' => 'optional bool|null',
        // Repository root path.
        // If this is non-null, you are probably using svn. In svn, this path
        // can be '' for the root, or 'trunk', or 'tags/something' etc.
        'repoRootPath' => 'optional string|null',
        // Lines to be highlighted.
        // This can be null (no line), line '2' or lines '2-3'.
        'lines' => 'optional string|null',
      ));

    // Define arguments who do not accept an empty string.
    // The 'repoRootPath' must not be listed here.
    $params_with_str_nonempty = array(
      'path' => 1,
      'branch' => 1,
      'lines' => 1,
    );

    // For all arguments, drop null.
    // For some arguments, also drop empty strings.
    foreach ($params as $key => $value) {
      if ($value === null
        || (isset($params_with_str_nonempty[$key]) && !strlen($value))) {
        unset($params[$key]);
      }
    }

    $defaults = array(
      'path' => '/',
      'branch' => null,
      'branchSupported' => null,
      'repoRootPath' => null,
      'lines' => null,
    );

    $params = $params + $defaults;

    $uri_base = coalesce($this->browseURI, '');
    $uri_base = rtrim($uri_base, '/');

    // Build the Subversion repo root path.
    // In git, this is an empty string.
    // In svn, this is 'trunk/' or '/' etc.
    $uri_root = '';
    if ($params['repoRootPath'] !== null) {
      $uri_root = trim($params['repoRootPath'], '/');
      $uri_root = phutil_escape_uri($uri_root); // Don't escape slashes.
      if ($uri_root !== '') {
        $uri_root .= '/';
      }
    }

    // Build the branch part of the URI.
    // In git, this becomes 'master/', 'main/', etc.
    // In svn, this is an empty string.
    $uri_branch = '';
    if ($params['branchSupported']) {
      $uri_branch = coalesce($params['branch'], $this->getDefaultBranch());
      $uri_branch = phutil_escape_uri_path_component($uri_branch); // Escape '/'
      $uri_branch .= '/';
    }

    $uri_path = ltrim($params['path'], '/');
    $uri_path = phutil_escape_uri($uri_path);

    $uri_lines = null;
    if ($params['lines']) {
      // TODO: We should encourage an #anchor, not a dollar.
      // https://we.phorge.it/T15670
      $uri_lines = '$'.phutil_escape_uri($params['lines']);
    }

    // This construction supports both git and Subversion.
    return "{$uri_base}/browse/{$uri_root}{$uri_branch}{$uri_path}{$uri_lines}";
  }

  public function getDefaultBranch() {
    $branch = idxv($this->parameters, array('fields', 'defaultBranch'));

    if ($branch === null) {
      return 'master';
    }

    return $branch;
  }

  public function isPermanentRef(ArcanistMarkerRef $ref) {
    $rules = idxv(
      $this->parameters,
      array('fields', 'refRules', 'permanentRefRules'));

    if ($rules === null) {
      return false;
    }

    // If the rules exist but there are no specified rules, treat every ref
    // as permanent.
    if (!$rules) {
      return true;
    }

    // TODO: It would be nice to unify evaluation of permanent ref rules
    // across Arcanist and Phabricator.

    $ref_name = $ref->getName();
    foreach ($rules as $rule) {
      $matches = null;
      if (preg_match('(^regexp\\((.*)\\)\z)', $rule, $matches)) {
        if (preg_match($matches[1], $ref_name)) {
          return true;
        }
      } else {
        if ($rule === $ref_name) {
          return true;
        }
      }
    }

    return false;
  }

}
