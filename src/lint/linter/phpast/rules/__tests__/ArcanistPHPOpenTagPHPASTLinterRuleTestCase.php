<?php

final class ArcanistPHPOpenTagPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/php-open-tag/');
  }

}
