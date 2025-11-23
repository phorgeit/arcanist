<?php

/**
 * Facilitates implementation of test cases for
 * @{class:ArcanistPHPASTLinterRule}s.
 */
abstract class ArcanistPHPASTLinterRuleTestCase
  extends ArcanistLinterTestCase {

  final protected function getLinter() {
    // Always include this rule so we get good messages if a test includes
    // a syntax error. No normal test should contain syntax errors.
    $syntax_rule = new ArcanistSyntaxErrorPHPASTLinterRule();

    $test_rule = $this->getLinterRule();

    $rules = array(
      $syntax_rule,
      $test_rule,
    );

    return id(new ArcanistPHPASTLinter())
      ->setRules($rules);
  }

  /**
   * Returns an instance of the linter rule being tested.
   *
   * @return ArcanistPHPASTLinterRule
   */
  protected function getLinterRule() {
    if (!PhutilPHPParserLibrary::isAvailable()) {
      try {
        PhutilPHPParserLibrary::build();
      } catch (Exception $ex) {
        $this->assertSkipped(
          pht('PHP-Parser is not available.'));
      }
    }

    $class = get_class($this);
    $matches = null;

    if (!preg_match('/^(\w+PHPASTLinterRule)TestCase$/', $class, $matches) ||
        !is_subclass_of($matches[1], ArcanistPHPASTLinterRule::class)) {
      throw new Exception(pht('Unable to infer linter rule class name.'));
    }

    return newv($matches[1], array());
  }

}
