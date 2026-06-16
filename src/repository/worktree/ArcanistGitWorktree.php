<?php

/**
 * Represents a single working tree of a Git repository (from `git worktree`).
 */
final class ArcanistGitWorktree
  extends Phobject {

  private $path;
  private $branch;

  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  public function getPath() {
    return $this->path;
  }

  public function setBranch($branch) {
    $this->branch = $branch;
    return $this;
  }

  /**
   * The short branch name checked out in this working tree, or null for a
   * detached HEAD or a bare repository.
   */
  public function getBranch() {
    return $this->branch;
  }

  /**
   * Parse the output of "git worktree list --porcelain" into a list of
   * @{class:ArcanistGitWorktree} objects.
   *
   * The porcelain format reports a block of attribute lines for each working
   * tree, with blocks separated by blank lines. For example:
   *
   *   worktree /path/to/main
   *   HEAD abcd...
   *   branch refs/heads/master
   *
   *   worktree /path/to/linked
   *   HEAD ef01...
   *   detached
   *
   * @param string $stdout Raw stdout from "git worktree list --porcelain".
   * @return list<ArcanistGitWorktree>
   */
  public static function newFromWorktreeList($stdout) {
    $worktrees = array();

    $current = null;
    $lines = phutil_split_lines($stdout, false);
    foreach ($lines as $line) {
      if (!strlen($line)) {
        if ($current !== null) {
          $worktrees[] = $current;
          $current = null;
        }
        continue;
      }

      $parts = explode(' ', $line, 2);
      $attribute = $parts[0];
      $value = (count($parts) > 1) ? $parts[1] : null;

      switch ($attribute) {
        case 'worktree':
          $current = id(new self())
            ->setPath($value);
          break;
        case 'branch':
          if ($current !== null) {
            // Values look like "refs/heads/<name>"; reduce to the short name.
            $matches = null;
            if (preg_match('(^refs/heads/(.*)\z)', $value, $matches)) {
              $current->setBranch($matches[1]);
            } else {
              $current->setBranch($value);
            }
          }
          break;
        default:
          // Ignore attributes other than "worktree" and "branch".
          break;
      }
    }

    if ($current !== null) {
      $worktrees[] = $current;
    }

    return $worktrees;
  }

}
