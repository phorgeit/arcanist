<?php

final class ArcanistLogicalOperatorsPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/logical-operators/');
  }

}
