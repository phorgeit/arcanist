<?php

final class ArcanistIsAShouldBeInstanceOfPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/is_a-should-be-instanceof/');
  }

}
