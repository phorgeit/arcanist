<?php

final class ArcanistNewlineAfterOpenTagPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/newline-after-open-tag/');
  }

}
