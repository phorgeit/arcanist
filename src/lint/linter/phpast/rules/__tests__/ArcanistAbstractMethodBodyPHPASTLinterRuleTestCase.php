<?php

final class ArcanistAbstractMethodBodyPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/abstract-method-body/');
  }

}
