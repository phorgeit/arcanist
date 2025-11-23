<?php

final class ArcanistDuplicateKeysInArrayPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/duplicate-keys-in-array/');
  }

}
