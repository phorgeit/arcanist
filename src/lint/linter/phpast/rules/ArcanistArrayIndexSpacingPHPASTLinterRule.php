<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\ArrayDimFetch
 */
final class ArcanistArrayIndexSpacingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 28;

  public function getLintName() {
    return pht('Spacing Before Array Index');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\ArrayDimFetch) {
      $trailing = $this->getNonsemanticTokensAfterNode(
        $node->var,
        $token_stream);
      $trailing_text = implode('', ppull($trailing, 'text'));

      if (preg_match('/^ +$/', $trailing_text)) {
        $this->raiseLintAtOffset(
          $node->var->getEndFilePos() + 1,
          pht('Convention: no spaces before index access.'),
          $trailing_text,
          '');
      }
    }
  }

}
