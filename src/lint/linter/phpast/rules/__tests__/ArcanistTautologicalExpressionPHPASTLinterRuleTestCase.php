<?php

final class ArcanistTautologicalExpressionPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/tautological-expression/');
  }

}
