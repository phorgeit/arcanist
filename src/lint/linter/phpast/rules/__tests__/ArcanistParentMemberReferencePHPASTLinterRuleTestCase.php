<?php

final class ArcanistParentMemberReferencePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(
      __DIR__.'/parent-member-references/');
  }

}
