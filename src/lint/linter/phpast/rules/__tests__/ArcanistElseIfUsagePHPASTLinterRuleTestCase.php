<?php

final class ArcanistElseIfUsagePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/elseif-usage/');
  }

}
