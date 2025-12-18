<?php

final class ArcanistInterfaceAbstractMethodPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/interface-abstract-method/');
  }

}
