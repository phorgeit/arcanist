<?php

final class ArcanistBlacklistedFunctionPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/blacklisted-function/');
  }

}
