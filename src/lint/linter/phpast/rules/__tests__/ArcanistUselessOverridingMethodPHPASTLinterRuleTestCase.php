<?php

final class ArcanistUselessOverridingMethodPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/useless-overriding-method/');
  }

}
