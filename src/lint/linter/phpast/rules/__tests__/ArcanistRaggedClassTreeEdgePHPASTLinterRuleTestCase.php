<?php

final class ArcanistRaggedClassTreeEdgePHPASTLinterRuleTestCase
  extends ArcanistPHPASTLinterRuleTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/ragged-classtree-edge/');
  }

}
