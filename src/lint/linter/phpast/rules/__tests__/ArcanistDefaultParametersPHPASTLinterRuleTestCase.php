<?php

final class ArcanistDefaultParametersPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/default-parameters/');
  }

}
