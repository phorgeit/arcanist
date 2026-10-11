<?php

final class ArcanistCppcheckLinterTestCase
  extends ArcanistExternalLinterTestCase {

  public function testLinter() {
    if (!class_exists('DOMDocument')) {
      $this->assertSkipped(
        pht('Required "dom" extension is not installed.'));
    }

    $this->executeTestsInDirectory(dirname(__FILE__).'/cppcheck/');
  }

}
