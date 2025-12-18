<?php

final class ArcanistHexadecimalNumericScalarCasingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/hexadecimal-numeric-scalar-casing/');
  }

}
