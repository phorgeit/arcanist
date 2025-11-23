<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Plus
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 */
final class ArcanistPlusOperatorOnStringsPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 21;

  public function getLintName() {
    return pht('Not String Concatenation');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\BinaryOp\Plus &&
      ($node->left instanceof PhpParser\Node\Scalar\String_ ||
       $node->right instanceof PhpParser\Node\Scalar\String_)) {

      $this->raiseLintAtNode(
        $node,
        pht(
          'In PHP, `%s` is the string concatenation operator, not `%s`. '.
          'This expression uses `%s` with a string literal as an operand.',
          '.',
          '+',
          '+'));
    }
  }

}
