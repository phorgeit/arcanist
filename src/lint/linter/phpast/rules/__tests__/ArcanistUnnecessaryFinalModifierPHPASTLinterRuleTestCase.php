<?php

final class ArcanistUnnecessaryFinalModifierPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/unnecessary-final-modifier/');
  }

}
