<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\Array_
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Scalar\InterpolatedString
 */
final class ArcanistArraySeparatorPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 48;

  public function getLintName() {
    return pht('Array Separator');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (!$node instanceof PhpParser\Node\Expr\Array_) {
      return;
    }

    // There is no need to check an empty array.
    if (!$node->items) {
      return;
    }

    $multiline = $node->getStartLine() !== $node->getEndLine();
    $last = last($node->items);
    $after = $token_stream[$last->getEndTokenPos() + 1];

    if ($multiline) {
      if (!$after->is(',')) {
        $kind = $last->value->getAttribute('kind');
        if (
          ($last->value instanceof PhpParser\Node\Scalar\String_ ||
           $last->value instanceof PhpParser\Node\Scalar\InterpolatedString) &&
          ($kind === PhpParser\Node\Scalar\String_::KIND_HEREDOC ||
           $kind === PhpParser\Node\Scalar\String_::KIND_NOWDOC)) {

          return;
        }

        $non_semantic_after = $this->getNonsemanticTokensAfterNode(
          $last,
          $token_stream);
        $non_semantic_after = implode('', ppull($non_semantic_after, 'text'));

        $original    = $this->getString($last, $token_stream);
        $replacement = $original.',';

        if (strpos($non_semantic_after, "\n") === false) {
          $original    .= $non_semantic_after;
          $replacement .= $non_semantic_after."\n".
            $this->getIndentation($node, $token_stream);
        }

        $this->raiseLintAtOffset(
          $last->getStartFilePos(),
          pht('Multi-lined arrays should have trailing commas.'),
          $original,
          $replacement);
      } else if ($last->getEndLine() === $node->getEndLine()) {
        $close = $token_stream[$node->getEndTokenPos()];
        $indentation = $this->getIndentation($node, $token_stream);

        $this->raiseLintAtToken(
          $close,
          pht('Closing parenthesis should be on a new line.'),
          "\n".$indentation.$close->text);
      }
    } else if ($after->is(',')) {
      $this->raiseLintAtToken(
        $after,
        pht('Single lined arrays should not have a trailing comma.'),
        '');
    }
  }

}
