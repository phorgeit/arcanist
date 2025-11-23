<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\LogicalAnd
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\LogicalOr
 */
final class ArcanistLogicalOperatorsPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 58;

  public function getLintName() {
    return pht('Logical Operators');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd) {
      $token = $this->getOperatorToken($node, $token_stream, 'and');

      $this->raiseLintAtToken(
        $token,
        pht('Use `%s` instead of `%s`.', '&&', 'and'),
        '&&');
    } else if ($node instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr) {
      $token = $this->getOperatorToken($node, $token_stream, 'or');

      $this->raiseLintAtToken(
        $token,
        pht('Use `%s` instead of `%s`.', '||', 'or'),
        '||');
    }
  }

  private function getOperatorToken(
    PhpParser\Node\Expr\BinaryOp $node,
    array $token_stream,
    string $operator) {

    $op_tokens = array_slice(
      $token_stream,
      $node->left->getEndTokenPos(),
      $node->right->getStartTokenPos() - $node->left->getEndTokenPos());

    $op_token = null;

    foreach ($op_tokens as $token) {
      if ($token->text === $operator) {
        $op_token = $token;
        break;
      }
    }

    return $op_token;
  }

}
