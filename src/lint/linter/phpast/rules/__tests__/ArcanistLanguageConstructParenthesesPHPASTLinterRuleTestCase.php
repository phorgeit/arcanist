<?php

final class ArcanistLanguageConstructParenthesesPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/language-construct-parentheses/');
  }

}
