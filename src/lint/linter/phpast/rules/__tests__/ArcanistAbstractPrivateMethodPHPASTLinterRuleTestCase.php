<?php

final class ArcanistAbstractPrivateMethodPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
        __DIR__.'/abstract-private-method/');
  }

}
