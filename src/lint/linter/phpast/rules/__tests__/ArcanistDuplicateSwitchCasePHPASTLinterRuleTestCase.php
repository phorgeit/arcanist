<?php

final class ArcanistDuplicateSwitchCasePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/duplicate-switch-case/');
  }

}
