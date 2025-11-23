<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\Cast
 */
final class ArcanistCastSpacingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 66;

  public function getLintName() {
    return pht('Cast Spacing');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\Cast) {
      $after = $this->getNonsemanticTokensAfter(
        $node->getStartTokenPos(),
        $token_stream);
      $after = head($after);

      if ($after) {
        $this->raiseLintAtToken(
          $after,
          pht('A cast statement must not be followed by a space.'),
          '');
      }
    }
  }

}
