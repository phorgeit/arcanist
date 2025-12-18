<?php

final class ArcanistBinaryExpressionSpacingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/binary-expression-spacing/');
  }

}
