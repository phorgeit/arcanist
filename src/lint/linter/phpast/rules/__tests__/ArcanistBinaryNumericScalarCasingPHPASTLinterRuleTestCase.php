<?php

final class ArcanistBinaryNumericScalarCasingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/binary-numeric-scalar-casing/');
  }

}
