<?php

final class ArcanistPaamayimNekudotayimSpacingPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/paamayim-nekudotayim-spacing/');
  }

}
