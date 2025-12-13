<?php

final class ArcanistArgumentAlignmentPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/argument-alignment/');
  }

}
