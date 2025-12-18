<?php

final class ArcanistGlobalVariablePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/global-variable/');
  }

}
