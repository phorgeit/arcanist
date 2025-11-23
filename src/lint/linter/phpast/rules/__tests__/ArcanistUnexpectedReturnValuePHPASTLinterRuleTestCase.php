<?php

final class ArcanistUnexpectedReturnValuePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/unexpected-return-value/');
  }

}
