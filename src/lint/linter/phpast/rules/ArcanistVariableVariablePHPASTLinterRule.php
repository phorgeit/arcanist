<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 */
final class ArcanistVariableVariablePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 3;

  public function getLintName() {
    return pht('Use of Variable Variable');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\Variable &&
      $node->name instanceof PhpParser\Node\Expr) {

      $this->raiseLintAtNode(
        $node,
        pht(
          'Rewrite this code to use an array. Variable variables are unclear '.
          'and hinder static analysis.'));
    }
  }

}
