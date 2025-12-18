<?php

final class ArcanistInvalidModifiersPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/invalid-modifiers/');
  }

}
