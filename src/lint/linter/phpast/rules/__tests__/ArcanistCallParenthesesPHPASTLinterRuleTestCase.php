<?php

final class ArcanistCallParenthesesPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/call-parentheses/');
  }

}
