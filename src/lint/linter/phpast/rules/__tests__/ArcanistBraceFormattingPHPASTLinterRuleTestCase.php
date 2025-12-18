<?php

final class ArcanistBraceFormattingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/brace-formatting/');
  }

}
