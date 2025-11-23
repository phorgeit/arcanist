<?php

final class ArcanistInvalidDefaultParameterPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/invalid-default-parameter/');
  }

}
