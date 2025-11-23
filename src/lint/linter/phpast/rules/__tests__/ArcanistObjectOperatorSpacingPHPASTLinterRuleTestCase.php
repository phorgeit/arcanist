<?php

final class ArcanistObjectOperatorSpacingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/object-operator-spacing/');
  }

}
