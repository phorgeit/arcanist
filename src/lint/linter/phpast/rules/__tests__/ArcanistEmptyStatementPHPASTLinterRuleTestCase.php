<?php

final class ArcanistEmptyStatementPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/empty-statement/');
  }

}
