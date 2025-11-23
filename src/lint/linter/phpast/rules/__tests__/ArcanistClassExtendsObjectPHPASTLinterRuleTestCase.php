<?php

final class ArcanistClassExtendsObjectPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/class-extends-object/');
  }

}
