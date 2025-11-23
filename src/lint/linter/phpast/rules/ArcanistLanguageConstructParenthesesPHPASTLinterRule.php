<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\Include_
 * @phutil-external-symbol class PhpParser\Node\Stmt
 * @phutil-external-symbol class PhpParser\Node\Stmt\Echo_
 */
final class ArcanistLanguageConstructParenthesesPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 46;

  public function getLintName() {
    return pht('Language Construct Parentheses');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\Echo_) {
      if (count($node->exprs) > 1) {
        return;
      }

      $expression = head($node->exprs);
    } else if ($node instanceof PhpParser\Node\Expr\Include_) {
      $expression = $node->expr;
    } else {
      return;
    }

    if (
      $token_stream[$expression->getStartTokenPos() - 1]->is('(') &&
      $token_stream[$expression->getEndTokenPos() + 1]->is(')')) {
      $opening_parenthesis = $expression->getStartTokenPos() - 1;

      $before = $this->getNonsemanticTokensBefore(
        $opening_parenthesis,
        $token_stream);

      $original = substr(
        $this->getString($node, $token_stream),
        $token_stream[$opening_parenthesis]->pos - $node->getStartFilePos());

      // Statements include the statement terminator as part of their node,
      // while expressions do not.
      if ($node instanceof PhpParser\Node\Stmt) {
        $original = rtrim($original, ';');
      }

      $replacement = $this->getString($expression, $token_stream);

      if (!$before) {
        $replacement = ' '.$replacement;
      }

      $this->raiseLintAtOffset(
        $token_stream[$opening_parenthesis]->pos,
        pht('Language constructs do not require parentheses.'),
        $original,
        $replacement);
    }
  }

}
