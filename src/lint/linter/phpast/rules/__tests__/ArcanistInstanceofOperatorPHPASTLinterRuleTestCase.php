<?php

final class ArcanistInstanceofOperatorPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/instanceof-operator/');
  }

}
