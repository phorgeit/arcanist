<?php

final class ArcanistParameterAlignmentPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/parameter-alignment/');
  }

}
