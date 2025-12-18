<?php

final class ArcanistNamingConventionsPHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/naming-conventions/');
  }

}
