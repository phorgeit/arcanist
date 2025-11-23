<?php

final class ArcanistFunctionCallShouldBeTypeCastPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/function-call-should-be-type-cast/');
  }

}
