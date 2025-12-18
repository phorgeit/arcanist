<?php

final class ArcanistDynamicDefinePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/dynamic-define/');
  }

}
