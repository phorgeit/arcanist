<?php

final class ArcanistSemicolonSpacingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/semicolon-spacing/');
  }

}
