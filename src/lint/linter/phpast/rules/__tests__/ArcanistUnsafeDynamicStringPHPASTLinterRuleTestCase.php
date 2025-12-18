<?php

final class ArcanistUnsafeDynamicStringPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/unsafe-dynamic-string/');
  }

}
