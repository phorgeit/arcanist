<?php

final class ArcanistPHPASTLinterTestCase extends ArcanistLinterTestCase {

  public function testLinter() {
    $this->executeTestsInDirectory(__DIR__.'/phpast/');
  }

}
