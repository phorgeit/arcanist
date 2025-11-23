<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Concat
 */
final class ArcanistConcatenationOperatorPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 44;

  public function getLintName() {
    return pht('Concatenation Spacing');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
      $op_token = -1;

      for (
        $i = $node->left->getEndTokenPos();
        $i < $node->right->getStartTokenPos();
        $i++) {

        if ($token_stream[$i]->is('.')) {
          $op_token = $i;
          break;
        }
      }

      if ($op_token === -1) {
        return;
      }

      $before = $this->getNonsemanticTokensBefore($op_token, $token_stream);
      $after = $this->getNonsemanticTokensAfter($op_token, $token_stream);

      if (!$before && !$after) {
        return;
      }

      $this->lintSpace($before);
      $this->lintSpace($after);
    }
  }

  private function lintSpace(array $tokens) {
    foreach ($tokens as $i => $token) {
      $next = idx($tokens, $i + 1);

      if (!$token->is(T_WHITESPACE)) {
        return;
      }

      if (strpos($token->text, "\n") !== false) {
        // If the whitespace has a newline, it's conventional.
        return;
      }

      if ($next && $next->is(T_COMMENT)) {
        return;
      }

      $this->raiseLintAtToken(
        $token,
        pht('Convention: no spaces around string concatenation operator.'),
        '');
    }
  }

}
