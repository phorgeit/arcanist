<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Global_
 */
final class ArcanistGlobalVariablePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 79;

  public function getLintName() {
    return pht('Global Variables');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\Global_) {
      $this->raiseLintAtNode(
        $node,
        pht(
          'Limit the use of global variables. Global variables are '.
          'generally a bad idea and should be avoided when possible.'));
    }
  }

}
