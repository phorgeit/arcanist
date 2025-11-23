<?php

final class ArcanistParseStrUsePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/parse_str-use/');
  }

}
