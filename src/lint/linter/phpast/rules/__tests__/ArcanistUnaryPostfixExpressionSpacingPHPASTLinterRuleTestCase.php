<?php

final class ArcanistUnaryPostfixExpressionSpacingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/unary-postfix-expression-spacing/');
  }

}
