<?php

final class ArcanistConcatenationOperatorPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/concatenation-operator/');
  }

}
