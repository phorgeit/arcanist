<?php

final class ArcanistImplodeArgumentOrderPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/implode-argument-order/');
  }

}
