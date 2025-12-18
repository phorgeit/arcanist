<?php

final class ArcanistThisReassignmentPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/this-reassignment/');
  }

}
