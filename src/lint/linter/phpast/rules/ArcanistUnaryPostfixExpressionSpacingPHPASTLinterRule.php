<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\PostInc
 * @phutil-external-symbol class PhpParser\Node\Expr\PostDec
 */
final class ArcanistUnaryPostfixExpressionSpacingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 75;

  public function getLintName() {
    return pht('Space Before Unary Postfix Operator');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\PostInc ||
      $node instanceof PhpParser\Node\Expr\PostDec) {

      $after = $this->getNonsemanticTokensAfterNode($node->var, $token_stream);

      if ($after) {
        $offset = head($after)->pos;
        $leading_text = implode('', ppull($after, 'text'));

        $this->raiseLintAtOffset(
          $offset,
          pht('Unary postfix operators should not be prefixed by whitespace.'),
          $leading_text,
          '');
      }
    }
  }

}
