<?php

final class ArcanistNoParentScopePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/no-parent-scope/');
  }

}
