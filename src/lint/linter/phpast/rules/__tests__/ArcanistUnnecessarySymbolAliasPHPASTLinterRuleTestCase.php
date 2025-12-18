<?php

final class ArcanistUnnecessarySymbolAliasPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/unnecessary-symbol-alias/');
  }

}
