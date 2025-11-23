<?php

final class ArcanistReusedIteratorReferencePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/reused-iterator-reference/');
  }

}
