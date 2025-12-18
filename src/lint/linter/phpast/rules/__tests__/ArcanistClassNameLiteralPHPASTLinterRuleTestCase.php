<?php

final class ArcanistClassNameLiteralPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/class-name-literal/');
  }

}
