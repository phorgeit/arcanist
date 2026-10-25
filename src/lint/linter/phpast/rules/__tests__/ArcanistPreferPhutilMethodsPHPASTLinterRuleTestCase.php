<?php

final class ArcanistPreferPhutilMethodsPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/prefer-function/');
  }

}
