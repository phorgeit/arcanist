<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 */
final class ArcanistDefaultParametersPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 60;

  public function getLintName() {
    return pht('Default Parameters');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\FunctionLike) {
      $default_found = false;

      foreach ($node->getParams() as $parameter) {
        if ($parameter->default) {
          $default_found = $parameter;
        } else if ($default_found) {
          $this->raiseLintAtNode(
            $default_found,
            pht(
              'Arguments with default values must be at the end '.
              'of the argument list.'));
        }
      }
    }
  }

}
