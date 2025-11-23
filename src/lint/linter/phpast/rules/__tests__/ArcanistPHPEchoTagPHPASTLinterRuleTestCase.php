<?php

final class ArcanistPHPEchoTagPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/php-echo-tag/');
  }

}
