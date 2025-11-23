<?php

final class ArcanistInterfaceMethodBodyPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/interface-method-body/');
  }

}
