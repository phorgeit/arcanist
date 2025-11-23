<?php

final class ArcanistModifierOrderingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/modifier-ordering/');
  }

}
