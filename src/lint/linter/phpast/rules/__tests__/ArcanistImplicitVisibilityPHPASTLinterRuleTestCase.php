<?php

final class ArcanistImplicitVisibilityPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/implicit-visibility/');
  }

}
