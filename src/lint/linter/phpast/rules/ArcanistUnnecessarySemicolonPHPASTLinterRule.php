<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Nop
 */
final class ArcanistUnnecessarySemicolonPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 56;

  public function getLintName() {
    return pht('Unnecessary Semicolon');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Stmt\Nop &&
      !$node->getComments()) {

      $this->raiseLintAtNode(
        $node,
        pht('Unnecessary semicolons after statement.'),
        '');
    }
  }

}
