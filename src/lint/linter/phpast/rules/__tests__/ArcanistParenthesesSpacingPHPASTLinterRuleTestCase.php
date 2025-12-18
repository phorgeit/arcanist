<?php

final class ArcanistParenthesesSpacingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/parentheses-spacing/');
  }

}
