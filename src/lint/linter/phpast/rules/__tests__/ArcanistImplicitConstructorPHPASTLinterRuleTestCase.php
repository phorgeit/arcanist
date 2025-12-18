<?php

final class ArcanistImplicitConstructorPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/implicit-constructor/');
  }

}
