<?php

final class ArcanistImplicitFallthroughPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/implicit-fallthrough/');
  }

}
