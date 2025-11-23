<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\Instanceof_
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Scalar\Int_
 * @phutil-external-symbol class PhpParser\Node\Scalar\Float_
 * @phutil-external-symbol class PhpParser\Node\Expr\ConstFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\ClassConstFetch
 */
final class ArcanistInstanceOfOperatorPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 69;

  public function getLintName() {
    return pht('`%s` Operator', 'instanceof');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\Instanceof_) {
      if (
        $node->expr instanceof PhpParser\Node\Scalar\String_ ||
        $node->expr instanceof PhpParser\Node\Scalar\Int_ ||
        $node->expr instanceof PhpParser\Node\Scalar\Float_ ||
        $node->expr instanceof PhpParser\Node\Expr\ConstFetch ||
        $node->expr instanceof PhpParser\Node\Expr\ClassConstFetch) {

        $this->raiseLintAtNode(
          $node->expr,
          pht(
            '`%s` expects an object instance, constant given.',
            'instanceof'));
      }
    }
  }

}
