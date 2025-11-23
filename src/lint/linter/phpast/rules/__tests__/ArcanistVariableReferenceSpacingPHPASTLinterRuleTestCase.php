<?php

final class ArcanistVariableReferenceSpacingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/variable-reference-spacing/');
  }

}
