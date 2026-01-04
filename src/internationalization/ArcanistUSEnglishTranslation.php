<?php

final class ArcanistUSEnglishTranslation extends PhutilTranslation {

  public function getLocaleCode() {
    return 'en_US';
  }

  protected function getTranslations() {
    return array(
      '%s locally modified path(s) are not included in this revision:' => array(
        'A locally modified path is not included in this revision:',
        'Locally modified paths are not included in this revision:',
      ),
      'These %s path(s) will NOT be committed. Commit this revision '.
      'anyway?' => array(
        'This path will NOT be committed. Commit this revision anyway?',
        'These paths will NOT be committed. Commit this revision anyway?',
      ),
      'Revision includes changes to %s path(s) that do not exist:' => array(
        'Revision includes changes to a path that does not exist:',
        'Revision includes changes to paths that do not exist:',
      ),

      'This diff includes %s file(s) which are not valid UTF-8 (they contain '.
      'invalid byte sequences). You can either stop this workflow and fix '.
      'these files, or continue. If you continue, these files will be '.
      'marked as binary.' => array(
        'This diff includes a file which is not valid UTF-8 (it has invalid '.
          'byte sequences). You can either stop this workflow and fix it, or '.
          'continue. If you continue, this file will be marked as binary.',
        'This diff includes files which are not valid UTF-8 (they contain '.
          'invalid byte sequences). You can either stop this workflow and fix '.
          'these files, or continue. If you continue, these files will be '.
          'marked as binary.',
      ),
      '%s AFFECTED FILE(S)' => array('AFFECTED FILE', 'AFFECTED FILES'),
      'Do you want to mark these %s file(s) as binary and continue?' => array(
        'Do you want to mark this file as binary and continue?',
        'Do you want to mark these files as binary and continue?',
      ),

      'Do you want to amend these %s change(s) to the current commit?' => array(
        'Do you want to amend this change to the current commit?',
        'Do you want to amend these changes to the current commit?',
      ),

      'Do you want to create a new commit with these %s change(s)?' => array(
        'Do you want to create a new commit with this change?',
        'Do you want to create a new commit with these changes?',
      ),

      '(To ignore these %s change(s), add them to "%s".)' => array(
        '(To ignore this change, add it to "%2$s".)',
        '(To ignore these changes, add them to "%2$s".)',
      ),

      '%s line(s)' => array('line', 'lines'),

      '%s assertion(s) passed.' => array(
        '%s assertion passed.',
        '%s assertions passed.',
      ),

      'Ignore these %s untracked file(s) and continue?' => array(
        'Ignore this untracked file and continue?',
        'Ignore these untracked files and continue?',
      ),

      '%s submodule(s) have uncommitted or untracked changes:' => array(
        'A submodule has uncommitted or untracked changes:',
        'Submodules have uncommitted or untracked changes:',
      ),

      'Ignore the changes to these %s submodule(s) and continue?' => array(
        'Ignore the changes to this submodule and continue?',
        'Ignore the changes to these submodules and continue?',
      ),

      'Updated %s librarie(s).' => array(
        'Updated library.',
        'Updated %s libraries.',
      ),
      'To go back to how things were before you ran "arc land", '.
      'run these %s command(s):' =>
      array(
        'To go back to how things were before you ran "arc land", '.
        'run this command:',
        'To go back to how things were before you ran "arc land", '.
        'run these commands:',
      ),
      'These %s symbol(s) do not exist in the remote. '.
      'They will be created as new branches:' =>
      array(
        'This symbol does not exist in the remote. '.
        'It will be created as a new branch:',
        'These symbols do not exist in the remote. '.
        'They will be created as new branches:',
      ),
      'You are using "--hold", so execution will stop '.
      'before the %s branche(s) are actually created. '.
      'You will be given instructions to create the branches.' =>
      array(
        'You are using "--hold", so execution will stop before the '.
        'branch is actually created. '.
        'You will be given instructions to create the branch.',
        'You are using "--hold", so execution will stop before the '.
        'branches are actually created. '.
        'You will be given instructions to create the branches.',
      ),
      'Create %s new branche(s) in the remote?' =>
      array(
        'Create a new branch in the remote?',
        'Create %s new branches in the remote?',
      ),
      'You are landing %s revision(s) which are currently in the state "%s", '.
      'indicating that you expect to revise them before moving forward.' =>
      array(
        'You are landing a revision which is currently in the state "%2$s", '.
        'indicating that you expect to revise it before moving forward.',
        'You are landing %s revisions which are currently in the state "%s", '.
        'indicating that you expect to revise them before moving forward.',
      ),
      'Normally, you should update these %s revision(s), submit them for '.
      'review, and wait for reviewers to accept them before you continue. '.
      'To resubmit a revision for review, either: update the revision with '.
      'revised changes; or use "Request Review" from the web interface.' =>
      array(
        'Normally, you should update this revision, submit it for review, '.
        'and wait for reviewers to accept it before you continue. '.
        'To resubmit a revision for review, either: update the revision with '.
        'revised changes; or use "Request Review" from the web interface.',
        'Normally, you should update these %s revisions, submit them for '.
        'review, and wait for reviewers to accept them before you continue. '.
        'To resubmit a revision for review, either: update the revision with '.
        'revised changes; or use "Request Review" from the web interface.',
      ),
      'These %s revision(s) have changes planned:' =>
      array(
        'This revision has changes planned:',
        'These %s revisions have changes planned:',
      ),
      'Land %s revision(s) with changes planned?' =>
      array(
        'Land %s revision with changes planned?',
        'Land %s revisions with changes planned?',
      ),
      'You are landing %s revision(s) which are already in the state "%s", '.
      'indicating that they have previously landed:' =>
      array(
        'You are landing a revision which is already in the state "%s", '.
        'indicating that it has previously landed:',
        'You are landing %s revisions which are already in the state "%s", '.
        'indicating that they have previously landed:',
      ),
      'Land %s revision(s) that are already published?' =>
      array(
        'Land %s revision that is already published?',
        'Land %s revisions that are already published?',
      ),
      'You are landing %s revision(s) which are not in state "Accepted", '.
      'indicating that they have not been accepted by reviewers. '.
      'Normally, you should land changes only once they have been accepted. '.
      'These revisions are in the wrong state:' =>
      array(
        'You are landing a revision which is not in state "Accepted", '.
        'indicating that it has not been accepted by reviewers. '.
        'Normally, you should land changes only once they have been '.
        'accepted. This revision is in the wrong state:',
        'You are landing %s revisions which are not in state "Accepted", '.
        'indicating that they have not been accepted by reviewers. '.
        'Normally, you should land changes only once they have been .'.
        'accepted. These revisions are in the wrong state:',
      ),
      'Land %s revision(s) in the wrong state?' =>
      array(
        'Land %s revision in the wrong state?',
        'Land %s revisions in the wrong state?',
      ),
      'The changes you are landing depend on %s open parent revision(s). '.
      'Usually, you should land parent revisions before landing the '.
      'changes which depend on them. These parent revisions are open:' =>
      array(
        'The changes you are landing depend on an open parent revision. '.
        'Usually, you should land parent revisions before landing the '.
        'changes which depend on them. This parent revision is open:',
        'The changes you are landing depend on %s open parent revisions. '.
        'Usually, you should land parent revisions before landing the '.
        'changes which depend on them. These parent revisions are open:',
      ),
      'Land changes that depend on %s open revision(s)?' =>
      array(
        'Land changes that depend on an open revision?',
        'Land changes that depend on %s open revisions?',
      ),
      '%s revision(s) have build failures or ongoing builds:' =>
      array(
        '%s revision has build failures or ongoing builds:',
        '%s revisions have build failures or ongoing builds:',
      ),
      'Land %s revision(s) anyway, despite ongoing and failed builds?' =>
      array(
        'Land %s revision anyway, despite ongoing and failed builds?',
        'Land %s revisions anyway, despite ongoing and failed builds?',
      ),
      '%s revision(s) have build failures:' =>
      array(
        '%s revision has build failures:',
        '%s revisions have build failures:',
      ),
      'Land %s revision(s) anyway, despite failed builds?' =>
      array(
        'Land %s revision anyway, despite failed builds?',
        'Land %s revisions anyway, despite failed builds?',
      ),
      '%s revision(s) have ongoing builds:' =>
      array(
        '%s revision has ongoing builds:',
        '%s revisions have ongoing builds:',
      ),
      'Land %s revision(s) anyway, despite ongoing builds?' =>
      array(
        'Land %s revision anyway, despite ongoing builds?',
        'Land %s revisions anyway, despite ongoing builds?',
      ),
      'Land %s commit(s)?' =>
      array(
        'Land %s commit?',
        'Land %s commits?',
      ),
      'These %s symbol(s) do not exist in the remote. '.
      'They will be created as new bookmarks:' =>
      array(
        'This symbol does not exist in the remote. '.
        'It will be created as a new bookmark:',
        'These symbols do not exist in the remote. '.
        'They will be created as new bookmarks:',
      ),
      'You are using "--hold", so execution will stop before the '.
      '%s bookmark(s) are actually created. '.
      'You will be given instructions to create the bookmarks.' =>
      array(
        'You are using "--hold", so execution will stop before the bookmark '.
        'is are actually created. You will be given instructions to '.
        'create the bookmark.',
        'You are using "--hold", so execution will stop before the bookmarks '.
        'are actually created. You will be given instructions to '.
        'create the bookmarks.',
      ),
      'Create %s new remote bookmark(s)?' =>
      array(
        'Create %s new remote bookmark?',
        'Create %s new remote bookmarks?',
      ),
      'To push changes manually, run these %s command(s):' =>
      array(
        'To push changes manually, run this command:',
        'To push changes manually, run these commands:',
      ),
      'Class contains %s %s method(s) and must therefore be declared `%s`.' =>
      array(
        'Class contains %s %s method and must therefore be declared `%s`.',
        'Class contains %s %s methods and must therefore be declared `%s`.',
      ),
      'Downloading "%s" (%s byte(s)) to "%s"...' =>
      array(
        array(
          'Downloading "%s" (%s byte) to "%s"...',
          'Downloading "%s" (%s bytes) to "%s"...',
        ),
      ),
      'Confirms landing more than %s commit(s) in a single operation.' =>
      array(
        'Confirms landing more than %s commit in a single operation.',
        'Confirms landing more than %s commits in a single operation.',
      ),
      '... (%s more byte(s)) ...' => array(
        '... (%s more byte) ...',
        '... (%s more bytes) ...',
      ),
      'CREATE %s BRANCHE(S)' => array(
        'CREATE BRANCH',
        'CREATE %s BRANCHES',
      ),
      '%s REVISION(S) HAVE CHANGES PLANNED' => array(
        '%s REVISION HAS CHANGES PLANNED',
        '%s REVISIONS HAVE CHANGES PLANNED',
      ),
      '%s REVISION(S) ARE ALREADY PUBLISHED' => array(
        '%s REVISION IS ALREADY PUBLISHED',
        '%s REVISIONS ARE ALREADY PUBLISHED',
      ),
      '%s REVISION(S) ARE NOT ACCEPTED' => array(
        '%s REVISION IS NOT ACCEPTED',
        '%s REVISIONS ARE NOT ACCEPTED',
      ),
      '%s OPEN PARENT REVISION(S)' => array(
        '%s OPEN PARENT REVISION',
        '%s OPEN PARENT REVISION(S)',
      ),
      '< ... %s more commits ... >' => array(
        '< ... %s more commit ... >',
        '< ... %s more commits ... >',
      ),
      'CREATE %s BOOKMARK(S)' => array(
        'CREATE BOOKMARK',
        'CREATE %s BOOKMARKS',
      ),
      '(This message was raised at line %s, '.
      'but the file only has %s line(s).)' => array(
        array(
          '(This message was raised at line %s, '.
          'but the file only has %s line.)',
          '(This message was raised at line %s, '.
          'but the file only has %s lines.)',
        ),
      ),
      '(... %s more revisions ...)' => array(
        '(... %s more revision ...)',
        '(... %s more revisions ...)',
      ),
      'Uploading chunks (%s chunks to upload).' => array(
        'Uploading chunks (%s chunk to upload).',
        'Uploading chunks (%s chunks to upload).',
      ),

    );
  }

}
