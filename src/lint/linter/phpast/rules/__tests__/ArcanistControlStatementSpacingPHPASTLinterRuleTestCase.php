<?php

final class ArcanistControlStatementSpacingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/control-statement-spacing/');
  }

}
