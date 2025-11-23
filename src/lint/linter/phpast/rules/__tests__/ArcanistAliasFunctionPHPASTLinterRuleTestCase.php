<?php

final class ArcanistAliasFunctionPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/alias-functions/');
  }

}
