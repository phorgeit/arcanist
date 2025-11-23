<?php

final class ArcanistReusedIteratorPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/reused-iterator/');
  }

}
