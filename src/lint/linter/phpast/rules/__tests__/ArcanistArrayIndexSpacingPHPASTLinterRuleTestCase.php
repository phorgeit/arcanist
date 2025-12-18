<?php

final class ArcanistArrayIndexSpacingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/array-index-spacing/');
  }

}
