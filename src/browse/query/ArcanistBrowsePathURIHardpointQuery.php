<?php

final class ArcanistBrowsePathURIHardpointQuery
  extends ArcanistBrowseURIHardpointQuery {

  const BROWSETYPE = 'path';

  public function loadHardpoint(array $refs, $hardpoint) {
    $refs = $this->getRefsWithSupportedTypes($refs);
    if (!$refs) {
      yield $this->yieldMap(array());
    }

    $repository_ref = (yield $this->yieldRepositoryRef());
    if (!$repository_ref) {
      yield $this->yieldMap(array());
    }

    $working_copy = $this->getWorkingCopy();
    $working_root = $working_copy->getPath();

    $results = array();
    foreach ($refs as $key => $ref) {
      $is_path = $ref->hasType(self::BROWSETYPE);

      $path = $ref->getToken();
      if ($path === null) {
        // If we're explicitly resolving no arguments as a path, treat it
        // as the current working directory.
        if ($is_path) {
          $path = '.';
        } else {
          continue;
        }
      }

      $lines = null;
      $parts = explode(':', $path);
      if (count($parts) > 1) {
        $lines = array_pop($parts);
      }
      $path = implode(':', $parts);

      $full_path = Filesystem::resolvePath($path);

      if (!Filesystem::pathExists($full_path)) {
        if (!$is_path) {
          continue;
        }
      }

      if ($full_path == $working_root) {
        $path = '';
      } else {
        $path = Filesystem::readablePath($full_path, $working_root);
      }

      // Define the repository root path, probably useful just for Subversion.
      $repo_root_path = null;
      if ($ref->getBranchSupported() === false) {
        // In software like SVN there is no native branch support. Really.
        // So, in SVN, we use getBranchName() to get the SVN
        // "branch", that is, the repository root path, and that's
        // a root relative path of the repository, like 'trunk' or 'tags/lol'.
        // Note that the empty string '' is a valid SVN repo root path
        // that may be returned by "svn info".
        $repo_root_path = $this
          ->getRepositoryApi()
          ->getBranchName();
      }

      $params = array(
        'path' => $path,
        'lines' => $lines,
        'branch' => $ref->getBranch(),
        'branchSupported' => $ref->getBranchSupported(),
        'repoRootPath' => $repo_root_path,
      );

      $uri = $repository_ref->newBrowseURI($params);

      $results[$key][] = $this->newBrowseURIRef()
        ->setURI($uri);
    }

    yield $this->yieldMap($results);
  }


}
