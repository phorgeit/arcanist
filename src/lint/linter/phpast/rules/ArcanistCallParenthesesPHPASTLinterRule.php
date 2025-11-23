<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Arg
 * @phutil-external-symbol class PhpParser\Node\Expr\Array_
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Expr\List_
 * @phutil-external-symbol class PhpParser\Node\Expr\MethodCall
 * @phutil-external-symbol class PhpParser\Node\Expr\NullsafeMethodCall
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Scalar\InterpolatedString
 */
final class ArcanistCallParenthesesPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 37;

  public function getLintName() {
    return pht('Call Formatting');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\Array_ ||
      $node instanceof PhpParser\Node\Expr\List_) {
      $kind = $node->getAttribute('kind');

      if (
        $kind === PhpParser\Node\Expr\Array_::KIND_SHORT ||
        $kind === PhpParser\Node\Expr\List_::KIND_ARRAY) {

        return;
      }

      $start = $node->getStartTokenPos();
      $end = $node->getEndTokenPos();
      $arguments = $node->items;
    } else if (
      $node instanceof PhpParser\Node\Expr\FuncCall ||
      $node instanceof PhpParser\Node\Expr\MethodCall ||
      $node instanceof PhpParser\Node\Expr\NullsafeMethodCall) {

      $start = $node->name->getEndTokenPos();
      $end = $node->args
        ? head($node->args)->getStartTokenPos()
        : $node->getEndTokenPos();
      $arguments = $node->args;
    } else {
      return;
    }

    $opening_parenthesis = -1;

    for ($i = $start; $i < $end; $i++) {
      if ($token_stream[$i]->is('(')) {
        $opening_parenthesis = $i;
        break;
      }
    }

    if ($opening_parenthesis === -1) {
      return;
    }

    $leading = $this->getNonsemanticTokensBefore(
      $opening_parenthesis,
      $token_stream);
    $leading_text = implode('', ppull($leading, 'text'));

    if (preg_match('/^\s+$/', $leading_text)) {
      $this->raiseLintAtOffset(
        $token_stream[$start + 1]->pos,
        pht('Convention: no spaces before opening parentheses.'),
        $leading_text,
        '');
    }

    if ($node instanceof PhpParser\Node\Expr\Array_) {
      return;
    }

    if ($arguments) {
      // If the last parameter of a call is a HEREDOC, don't apply this rule.
      $last = last($arguments);

      if ($last instanceof PhpParser\Node\Arg) {
        $kind = $last->value->getAttribute('kind');
        if (
          ($last->value instanceof PhpParser\Node\Scalar\String_ ||
           $last->value instanceof PhpParser\Node\Scalar\InterpolatedString) &&
          ($kind === PhpParser\Node\Scalar\String_::KIND_HEREDOC ||
           $kind === PhpParser\Node\Scalar\String_::KIND_NOWDOC)) {

          return;
        }
      }
    }

    $end = $arguments
      ? last($arguments)->getEndTokenPos()
      : $opening_parenthesis;

    $trailing = $this->getNonsemanticTokensAfter($end, $token_stream);
    $trailing_text = implode('', ppull($trailing, 'text'));

    if (preg_match('/^\s+$/', $trailing_text)) {
      $this->raiseLintAtOffset(
        $token_stream[$node->getEndTokenPos()]->pos - strlen($trailing_text),
        pht('Convention: no spaces before closing parentheses.'),
        $trailing_text,
        '');
    }
  }

}
