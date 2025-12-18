<?php

final class ArcanistUnaryPrefixExpressionSpacingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/unary-prefix-expression-spacing/');
  }

}
