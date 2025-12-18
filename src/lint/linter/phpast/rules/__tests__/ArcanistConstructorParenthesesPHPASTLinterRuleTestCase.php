<?php

final class ArcanistConstructorParenthesesPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/constructor-parentheses/');
  }

}
