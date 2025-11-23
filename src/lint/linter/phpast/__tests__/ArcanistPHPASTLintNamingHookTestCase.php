<?php

/**
 * Test cases for @{class:ArcanistPHPASTLintNamingHook}.
 */
final class ArcanistPHPASTLintNamingHookTestCase
  extends PhutilTestCase {

  public function testCaseUtilities() {
    $tests = array(
      'UpperCamelCase'                   => array(1, 0, 0, 0),
      'UpperCamelCaseROFL'               => array(1, 0, 0, 0),

      'lowerCamelCase'                   => array(0, 1, 0, 0),
      'lowerCamelCaseROFL'               => array(0, 1, 0, 0),

      'UPPERCASE_WITH_UNDERSCORES'       => array(0, 0, 1, 0),
      '_UPPERCASE_WITH_UNDERSCORES_'     => array(0, 0, 1, 0),
      '__UPPERCASE__WITH__UNDERSCORES__' => array(0, 0, 1, 0),

      'lowercase_with_underscores'       => array(0, 0, 0, 1),
      '_lowercase_with_underscores_'     => array(0, 0, 0, 1),
      '__lowercase__with__underscores__' => array(0, 0, 0, 1),

      'mixedCASE_NoNsEnSe'               => array(0, 0, 0, 0),
    );

    foreach ($tests as $test => $expect) {
      $this->assertEqual(
        $expect[0],
        ArcanistPHPASTLintNamingHook::isUpperCamelCase($test),
        pht("UpperCamelCase: '%s'", $test));
      $this->assertEqual(
        $expect[1],
        ArcanistPHPASTLintNamingHook::isLowerCamelCase($test),
        pht("lowerCamelCase: '%s'", $test));
      $this->assertEqual(
        $expect[2],
        ArcanistPHPASTLintNamingHook::isUppercaseWithUnderscores($test),
        pht("UPPERCASE_WITH_UNDERSCORES: '%s'", $test));
      $this->assertEqual(
        $expect[3],
        ArcanistPHPASTLintNamingHook::isLowercaseWithUnderscores($test),
        pht("lowercase_with_underscores: '%s'", $test));
    }
  }

  public function testStripUtilities() {
    // Function/method stripping.
    $this->assertEqual(
      'construct',
      ArcanistPHPASTLintNamingHook::stripPHPFunction('construct'));
    $this->assertEqual(
      'construct',
      ArcanistPHPASTLintNamingHook::stripPHPFunction('__construct'));
  }

}
