<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\Assign
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 */
final class ArcanistThisReassignmentPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 91;

  public function getLintName() {
    return pht('`%s` Reassignment', '$this');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\Assign &&
      $node->var instanceof PhpParser\Node\Expr\Variable &&
      $node->var->name === 'this') {

      $this->raiseLintAtNode(
        $node,
        pht(
          '`%s` cannot be re-assigned. '.
          'This construct will cause a PHP fatal error.',
          '$this'));
    }
  }

}
