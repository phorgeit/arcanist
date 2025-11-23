<?php

final class ArcanistCallTimePassByReferencePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/call-time-pass-by-reference/');
  }

}
