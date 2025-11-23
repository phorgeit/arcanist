<?php

final class ArcanistPHPCompatibilityPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/php-compatibility/');
  }

}
