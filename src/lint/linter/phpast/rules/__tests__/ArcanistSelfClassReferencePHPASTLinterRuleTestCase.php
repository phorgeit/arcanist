<?php

final class ArcanistSelfClassReferencePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/self-class-reference/');
  }

}
