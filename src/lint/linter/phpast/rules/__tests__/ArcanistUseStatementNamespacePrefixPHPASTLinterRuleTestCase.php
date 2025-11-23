<?php

final class ArcanistUseStatementNamespacePrefixPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/use-statement-namespace-prefix/');
  }

}
