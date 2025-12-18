<?php

final class ArcanistContinueInsideSwitchPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/continue-inside-switch/');
  }

}
