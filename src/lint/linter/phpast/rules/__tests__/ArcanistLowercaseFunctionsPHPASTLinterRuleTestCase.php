<?php

final class ArcanistLowercaseFunctionsPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/lowercase-functions/');
  }

}
