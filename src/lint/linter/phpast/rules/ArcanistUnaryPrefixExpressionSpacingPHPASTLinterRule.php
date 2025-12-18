<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\Exit_
 * @phutil-external-symbol class PhpParser\Node\Expr\PreInc
 * @phutil-external-symbol class PhpParser\Node\Expr\PreDec
 * @phutil-external-symbol class PhpParser\Node\Expr\ErrorSuppress
 * @phutil-external-symbol class PhpParser\Node\Expr\UnaryMinus
 * @phutil-external-symbol class PhpParser\Node\Expr\UnaryPlus
 * @phutil-external-symbol class PhpParser\Node\Expr\BitwiseNot
 * @phutil-external-symbol class PhpParser\Node\Expr\BooleanNot
 */
final class ArcanistUnaryPrefixExpressionSpacingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 73;

  public function getLintName() {
    return pht('Space After Unary Prefix Operator');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
     if ($node instanceof PhpParser\Node\Expr\Exit_) {
       if (!$node->expr) {
         if ($token_stream[$node->getStartTokenPos() + 1]->is(T_WHITESPACE)) {
           $this->raiseLintAtToken(
             $token_stream[$node->getStartTokenPos() + 1],
             pht(
               'Unary prefix operators should not be followed by whitespace.'),
             '');
         }

         return;
       }

      $expression = $node->expr;
    } else if (
      $node instanceof PhpParser\Node\Expr\PreInc ||
      $node instanceof PhpParser\Node\Expr\PreDec) {
      $expression = $node->var;
    } else if (
      $node instanceof PhpParser\Node\Expr\ErrorSuppress ||
      $node instanceof PhpParser\Node\Expr\UnaryMinus ||
      $node instanceof PhpParser\Node\Expr\UnaryPlus ||
      $node instanceof PhpParser\Node\Expr\BitwiseNot ||
      $node instanceof PhpParser\Node\Expr\BooleanNot) {
      $expression = $node->expr;
    } else {
      return;
    }

    $before = $this->getNonsemanticTokensBeforeNode($expression, $token_stream);

    if ($before) {
      $this->raiseLintAtOffset(
        head($before)->pos,
        pht('Unary prefix operators should not be followed by whitespace.'),
        implode('', ppull($before, 'text')),
        '');
    }
  }

}
