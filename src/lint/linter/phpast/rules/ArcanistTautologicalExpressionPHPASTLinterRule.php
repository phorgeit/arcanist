<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\BooleanOr
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\BooleanAnd
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Minus
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Div
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Equal
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\NotEqual
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Identical
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\NotIdentical
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Smaller
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Greater
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignOp\Minus
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignOp\Div
 * @phutil-external-symbol class PhpParser\Node\Expr\ConstFetch
 * @phutil-external-symbol class PhpParser\Node\Scalar\Int_
 * @phutil-external-symbol class PhpParser\Node\Scalar\Float_
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Expr\Array_
 */
final class ArcanistTautologicalExpressionPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 20;

  public function getLintName() {
    return pht('Tautological Expression');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
      $node instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {

      $operator = $node->getOperatorSigil();
      $left = $this->evaluateStaticBoolean($node->left);
      $right = $this->evaluateStaticBoolean($node->right);

      if (
        ($operator === '||' && ($left === true || $right === true)) ||
        ($operator === '&&' && ($left === false || $right === false))) {
        $this->raiseLintAtNode(
          $node,
          pht(
            'The logical value of this expression is static. '.
            'Did you forget to remove some debugging code?'));
      }
    } else if (
      $node instanceof PhpParser\Node\Expr\BinaryOp\Minus ||
      $node instanceof PhpParser\Node\Expr\BinaryOp\Div ||
      $node instanceof PhpParser\Node\Expr\BinaryOp\Equal ||
      $node instanceof PhpParser\Node\Expr\BinaryOp\NotEqual ||
      $node instanceof PhpParser\Node\Expr\BinaryOp\Identical ||
      $node instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical ||
      $node instanceof PhpParser\Node\Expr\BinaryOp\Smaller ||
      $node instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual ||
      $node instanceof PhpParser\Node\Expr\BinaryOp\Greater ||
      $node instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual) {

      $left = $this->getSemanticString($node->left, $token_stream);
      $right = $this->getSemanticString($node->right, $token_stream);

      if ($left === $right) {
        $this->raiseLintAtNode(
          $node,
          pht(
            'Both sides of this expression are identical, so it always '.
            'evaluates to a constant.'));
      }
    } else if (
      $node instanceof PhpParser\Node\Expr\AssignOp\Minus ||
      $node instanceof PhpParser\Node\Expr\AssignOp\Div) {

      $left = $this->getSemanticString($node->var, $token_stream);
      $right = $this->getSemanticString($node->expr, $token_stream);

      if ($left === $right) {
        $this->raiseLintAtNode(
          $node,
          pht(
            'Both sides of this expression are identical, so it always '.
            'evaluates to a constant.'));
      }
    }
  }

  private function evaluateStaticBoolean(PhpParser\Node $node) {
    if ($node instanceof PhpParser\Node\Expr\ConstFetch) {
      switch ($node->name->toLowerString()) {
        case 'null':
        case 'false':
          return false;
        case 'true':
          return true;
      }
    } else if (
      $node instanceof PhpParser\Node\Scalar\Int_ ||
      $node instanceof PhpParser\Node\Scalar\Float_ ||
      $node instanceof PhpParser\Node\Scalar\String_) {

      return (bool)$node->value;
    } else if ($node instanceof PhpParser\Node\Expr\Array_) {
      return (bool)$node->items;
    }

    return null;
  }

}
