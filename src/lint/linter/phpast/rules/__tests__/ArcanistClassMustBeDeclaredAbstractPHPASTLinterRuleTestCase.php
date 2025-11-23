<?php

final class ArcanistClassMustBeDeclaredAbstractPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/class-must-be-declared-abstract/');
  }

}
