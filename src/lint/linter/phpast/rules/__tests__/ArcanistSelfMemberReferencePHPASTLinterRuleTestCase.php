<?php

final class ArcanistSelfMemberReferencePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/self-member-reference/');
  }

}
