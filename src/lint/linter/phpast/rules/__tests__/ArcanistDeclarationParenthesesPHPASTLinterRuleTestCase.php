<?php

final class ArcanistDeclarationParenthesesPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/declaration-parentheses/');
  }

}
