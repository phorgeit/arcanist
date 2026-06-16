<?php

final class ArcanistGitWorktreeTestCase
  extends PhutilTestCase {

  public function testWorktreeQueriesAgainstRealGit() {
    if (phutil_is_windows()) {
      $this->assertSkipped(pht('This test is not supported under Windows.'));
    }

    if (!Filesystem::binaryExists('git')) {
      $this->assertSkipped(pht('Git is not installed.'));
    }

    $fixture = PhutilDirectoryFixture::newEmptyFixture();
    $old_cwd = getcwd();
    try {
      $this->runRealGitWorktreeTests($fixture->getPath());
    } finally {
      chdir($old_cwd);
    }
  }

  private function runRealGitWorktreeTests($root) {
    $main = $root.'/main';
    $feature_path = $root.'/wt-feature';
    $detached_path = $root.'/wt-detached';
    $locked_path = $root.'/wt-locked';

    execx('git init %s', $main);
    chdir($main);
    execx('git config user.email %s', 'test@example.com');
    execx('git config user.name %s', 'Test');
    execx('git commit --allow-empty -m %s', 'Initial commit.');

    $working_copy = ArcanistWorkingCopyIdentity::newFromPath($main);
    $configuration_manager = new ArcanistConfigurationManager();
    $configuration_manager->setWorkingCopyIdentity($working_copy);
    $api = ArcanistRepositoryAPI::newAPIFromConfigurationManager(
      $configuration_manager);

    // "git worktree list --porcelain" is only available in Git 2.7 and newer.
    if (version_compare($api->getGitVersion(), '2.7.0', '<')) {
      $this->assertSkipped(pht('Worktree queries require Git 2.7.0 or newer.'));
    }

    // Read the primary worktree's branch name rather than assuming it, so the
    // test does not depend on the local "init.defaultBranch" setting.
    list($primary_branch) = execx('git symbolic-ref --short HEAD');
    $primary_branch = trim($primary_branch);

    // A linked worktree holding a branch.
    execx('git branch %s', 'feature');
    execx('git worktree add %s %s', $feature_path, 'feature');

    // A linked worktree with a detached HEAD.
    execx('git worktree add --detach %s HEAD', $detached_path);

    // A linked worktree carrying an extra "locked" attribute.
    execx('git worktree add -b %s %s', 'locktopic', $locked_path);
    execx('git worktree lock %s', $locked_path);

    $branch_by_path = array();
    foreach ($api->getWorktrees() as $worktree) {
      $branch_by_path[$worktree->getPath()] = $worktree->getBranch();
    }

    $main_resolved = Filesystem::resolvePath($main);
    $feature_resolved = Filesystem::resolvePath($feature_path);
    $detached_resolved = Filesystem::resolvePath($detached_path);
    $locked_resolved = Filesystem::resolvePath($locked_path);

    $this->assertEqual(4, count($branch_by_path));

    // Primary and branch-holding linked worktrees report their branch.
    $this->assertEqual($primary_branch, $branch_by_path[$main_resolved]);
    $this->assertEqual('feature', $branch_by_path[$feature_resolved]);

    // A detached worktree reports no branch.
    $this->assertTrue(array_key_exists($detached_resolved, $branch_by_path));
    $this->assertEqual(null, $branch_by_path[$detached_resolved]);

    // The "locked" attribute is ignored; the branch is still parsed.
    $this->assertEqual('locktopic', $branch_by_path[$locked_resolved]);

    $this->assertEqual(
      $feature_resolved,
      $api->getWorktreeForBranch('feature'));
    $this->assertEqual(
      $locked_resolved,
      $api->getWorktreeForBranch('locktopic'));
    $this->assertEqual(
      $main_resolved,
      $api->getWorktreeForBranch($primary_branch));
    $this->assertEqual(null, $api->getWorktreeForBranch('nonexistent'));
  }

}
