<?php

final class ArcanistPHPCloseTagPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/php-close-tag/');
  }

}
