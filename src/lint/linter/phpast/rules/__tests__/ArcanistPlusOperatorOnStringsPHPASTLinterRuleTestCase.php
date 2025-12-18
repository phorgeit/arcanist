<?php

final class ArcanistPlusOperatorOnStringsPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/plus-operator-on-strings/');
  }

}
