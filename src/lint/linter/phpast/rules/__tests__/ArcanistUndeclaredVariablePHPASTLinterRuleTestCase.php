<?php

final class ArcanistUndeclaredVariablePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/undeclared-variable/');
  }

}
