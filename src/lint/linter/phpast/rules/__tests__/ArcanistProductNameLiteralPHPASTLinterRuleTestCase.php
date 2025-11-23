<?php

final class ArcanistProductNameLiteralPHPASTLinterRuleTestCase extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/product-name-literal/');
  }

}
