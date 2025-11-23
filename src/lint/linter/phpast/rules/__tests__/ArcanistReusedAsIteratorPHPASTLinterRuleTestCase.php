<?php

final class ArcanistReusedAsIteratorPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/reused-as-iterator/');
  }

}
