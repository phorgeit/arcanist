<?php

final class ArcanistGitWorkEngine
  extends ArcanistWorkEngine {

  protected function getDefaultStartSymbol() {
    $api = $this->getRepositoryAPI();

    // NOTE: In Git, we're trying to find the current branch name because the
    // behavior of "--track" depends on the symbol we pass.

    $marker = $api->newMarkerRefQuery()
      ->withIsActive(true)
      ->withMarkerTypes(array(ArcanistMarkerRef::TYPE_BRANCH))
      ->executeOne();
    if ($marker) {
      return $marker->getName();
    }

    return $api->getWorkingCopyRevision();
  }

  protected function newMarker($symbol, $start) {
    $api = $this->getRepositoryAPI();
    $log = $this->getLogEngine();

    $log->writeStatus(
      pht('NEW BRANCH'),
      pht(
        'Creating new branch "%s" from "%s".',
        $symbol,
        $start));

    $future = $api->newFuture(
      'checkout --track -b %s %s --',
      $symbol,
      $start);
    $future->resolve();
  }

  protected function moveToMarker(ArcanistMarkerRef $marker) {
    $api = $this->getRepositoryAPI();
    $log = $this->getLogEngine();

    $log->writeStatus(
      pht('BRANCH'),
      pht(
        'Checking out branch "%s".',
        $marker->getName()));

    $future = $api->newFuture(
      'checkout %s --',
      $marker->getName());
    $future->resolve();
  }

  protected function handleWorkOnTaskBranch(ArcanistTaskRef $task_ref) {
    $monogram = $task_ref->getMonogram();
    $task_name = $task_ref->getName();
    $api = $this->getRepositoryAPI();

    // Check if the branch exists.
    $existing_branches =
      $api->execxLocal(
        'branch --list \'%s-*\' --format \'%%(refname:short)\''
        , $monogram);
    $existing_branches = trim($existing_branches[0]);
    $existing_branches = explode("\n", $existing_branches);

    // Do not allow more than one branch.
    if (count($existing_branches) > 1) {
      throw new InvalidArgumentException(
        pht(
          "More than one branch matching task ID '%s' exists.".
          " Use 'git checkout' instead.",
          $monogram));
    }

    $branch_name = '';
    if ($existing_branches) {
      $branch_name = trim($existing_branches[0]);
    }

    // default to current branch if no "--start" argument is supplied
    $start = $this->getStartArgument()
      ?? $api->getBranchName();

    if ($start === null) {
      // Detached HEAD and no explicit --start: guide the user
      throw new ArcanistUsageException(
        pht(
          "You're in a detached HEAD and no start point was given.\n".
          "Run `%s` or switch to a branch first.",
          'arc work --start <base-branch> '.$task_ref->getMonogram())
      );
    }

    if ($branch_name !== '') {
      $api->execxLocal('checkout %s', $branch_name);
    } else {
      $branch_name = $this->sanitizeBranchName($monogram, $task_name);
      $this->newMarker($branch_name, $start);
    }
    return $branch_name;
  }

  public function sanitizeBranchName($monogram, $task_name) {
    // 1st pattern ensures compliance with git rules
    // ( e.g. allows "]" but not "[" )
    // Don't change it. Change $pattern2 instead.
    // see https://git-scm.com/docs/git-check-ref-format
    $pattern =
      '#(^[./]+|//|/\.+|\.{2,}|@{|[/.]+$|^@$|[~^:\x00-\x20\x7F?*\[\\\\])#u';
    $branch_name = preg_replace($pattern, '-', $task_name);
    $branch_name = trim($branch_name, '-');

    // 2nd pattern further restricts branch name for readability.
    // Allows only: unicode alphanumeric
    // All else collapsed to '-'
    // Can add other allowed characters here in the future
    $pattern2 = '#([^'. // negation
      '\p{L}'. // allow unicode alpha
      '\p{N}'. // allow unicode numeric
      // '\p{Extended_Pictographic}'. // allow base emoji
      ']+)#u';
    $branch_name = preg_replace($pattern2, '-', $branch_name);

    $branch_name = mb_convert_case($branch_name, MB_CASE_LOWER, 'UTF-8');
    $branch_name = $monogram.'-'.$branch_name;
    // collapse multiple '-' to single '-'
    $branch_name = preg_replace('#-+#', '-', $branch_name);
    // GitHub max is 244 bytes. Limit to 60 char = 60 bytes (if 100% ascii)
    // to 231 bytes (3 ascii + 4*57 unicode, e.g "T1-[unicode]")
    $branch_name = rtrim(mb_substr($branch_name, 0, 60), '-');
    return $branch_name;
  }

}
