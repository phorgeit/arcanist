<?php

final class ArcanistLambdaFuncFunctionPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/__lambda_func-function/');
  }

}
