<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignRef
 */
final class ArcanistVariableReferenceSpacingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 123;

  public function getLintName() {
    return pht('Variable Reference Spacing');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\AssignRef) {
      $before = $this->getNonsemanticTokensBeforeNode(
        $node->expr,
        $token_stream);

      if ($before) {
        $this->raiseLintAtOffset(
          head($before)->pos,
          pht('Variable references should not be prefixed with whitespace.'),
          implode('', ppull($before, 'text')),
          '');
      }
    }
  }

}
