<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\If_
 * @phutil-external-symbol class PhpParser\Node\Stmt\For_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Foreach_
 * @phutil-external-symbol class PhpParser\Node\Stmt\While_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Switch_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Catch_
 * @phutil-external-symbol class PhpParser\Node\Expr\Match_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Else_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Do_
 * @phutil-external-symbol class PhpParser\Token
 */
final class ArcanistControlStatementSpacingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 26;

  public function getLintName() {
    return pht('Space After Control Statement');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Stmt\If_ ||
      $node instanceof PhpParser\Node\Stmt\For_ ||
      $node instanceof PhpParser\Node\Stmt\Foreach_ ||
      $node instanceof PhpParser\Node\Stmt\While_ ||
      $node instanceof PhpParser\Node\Stmt\Switch_ ||
      $node instanceof PhpParser\Node\Stmt\Catch_ ||
      $node instanceof PhpParser\Node\Expr\Match_ ||
      $node instanceof PhpParser\Node\Stmt\Else_) {

      $trailing_tokens = $this->getNonsemanticTokensAfter(
        $node->getStartTokenPos(),
        $token_stream);

      $this->checkSpace(
        $token_stream[$node->getStartTokenPos()],
        $trailing_tokens,
        $node instanceof PhpParser\Node\Stmt\Else_);
    } else if ($node instanceof PhpParser\Node\Stmt\Do_) {
      $do_trailing_tokens = $this->getNonsemanticTokensAfter(
        $node->getStartTokenPos(),
        $token_stream);

      $this->checkSpace(
        $token_stream[$node->getStartTokenPos()],
        $do_trailing_tokens,
        true);

      $while = -1;

      for (
        $i = $node->cond->getStartTokenPos();
        $i > $node->getStartTokenPos(); $i--) {

        if ($token_stream[$i]->is(T_WHILE)) {
          $while = $i;
          break;
        }
      }

      if ($while === -1) {
        return;
      }

      $while_trailing_tokens = $this->getNonsemanticTokensAfter(
        $while,
        $token_stream);

      $this->checkSpace(
        $token_stream[$while],
        $while_trailing_tokens,
        false);
    }
  }

  private function checkSpace(
    PhpParser\Token $control,
    array $non_semantic_tokens,
    bool $allow_any_whitespace) {

    if (!$non_semantic_tokens) {
      $this->raiseLintAtToken(
        $control,
        pht('Convention: put a space after control statements.'),
        $control->text.' ');
    } else if (count($non_semantic_tokens) === 1) {
      $space = head($non_semantic_tokens);

      if ($space->is(T_WHITESPACE)) {
        // If we have an else/do clause without braces, $space could be
        // a single white space. e.g.,
        //
        //   if ($x)
        //     echo 'foo'
        //   else          // <- $space is not " " but "\n  ".
        //     echo 'bar'
        //
        // We just require it starts with either a whitespace or a newline.
        if ($allow_any_whitespace) {
          return;
        }

        if ($space->text !== ' ') {
          $this->raiseLintAtToken(
            $space,
            pht(
              'Convention: put a single space after control statements.'),
            ' ');
        }
      }
    }
  }

}
