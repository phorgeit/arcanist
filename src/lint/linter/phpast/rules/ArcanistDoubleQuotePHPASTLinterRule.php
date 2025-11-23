<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Concat
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Scalar\InterpolatedString
 */
final class ArcanistDoubleQuotePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 41;

  public function getLintName() {
    return pht('Unnecessary Double Quotes');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    $parent = $node->getAttribute('parent');
    $invalid_strings = array();

    if ($node instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
      if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
        return;
      }

      foreach ($this->unwrapConcatenation($node) as $string) {
        $kind = $string->getAttribute('kind');

        if (
          $string instanceof PhpParser\Node\Scalar\InterpolatedString ||
          $kind !== PhpParser\Node\Scalar\String_::KIND_DOUBLE_QUOTED ||
          $this->requiresDoubleQuotes($string->getAttribute('rawValue'))) {
          return;
        }

        $invalid_strings[] = $string;
      }
    } else if ($node instanceof PhpParser\Node\Scalar\String_) {
      $kind = $node->getAttribute('kind');

      if (
        $kind !== PhpParser\Node\Scalar\String_::KIND_DOUBLE_QUOTED ||
        $parent instanceof PhpParser\Node\Expr\BinaryOp\Concat ||
        $this->requiresDoubleQuotes($node->getAttribute('rawValue'))) {
        return;
      }

      $invalid_strings[] = $node;
    } else {
      return;
    }

    foreach ($invalid_strings as $invalid_string) {
      $contents = trim($invalid_string->getAttribute('rawValue'), '"');

      $this->raiseLintAtNode(
        $invalid_string,
        pht(
          'String does not require double quotes. For consistency, '.
          'prefer single quotes.'),
        "'".str_replace('\"', '"', $contents)."'",
        $token_stream);
    }
  }

  private function requiresDoubleQuotes(string $contents) {
    // Double quoted strings are allowed when the string contains the
    // following characters.
    static $allowed_chars = array(
      '\n',
      '\r',
      '\t',
      '\v',
      '\e',
      '\f',
      '\'',
      '\0',
      '\1',
      '\2',
      '\3',
      '\4',
      '\5',
      '\6',
      '\7',
      '\x',
    );

    foreach ($allowed_chars as $allowed_char) {
      if (strpos($contents, $allowed_char) !== false) {
        return true;
      }
    }

    return false;
  }

  private function unwrapConcatenation(
    PhpParser\Node\Expr\BinaryOp\Concat $concatenation) {

    do {
      $right = $concatenation->right;

      if (
        $right instanceof PhpParser\Node\Scalar\String_ ||
        $right instanceof PhpParser\Node\Scalar\InterpolatedString) {
        yield $right;
      }

      $concatenation = $concatenation->left;

      if (
        $concatenation instanceof PhpParser\Node\Scalar\String_ ||
        $concatenation instanceof PhpParser\Node\Scalar\InterpolatedString) {
        yield $concatenation;
      }
    } while ($concatenation instanceof PhpParser\Node\Expr\BinaryOp\Concat);
  }

}
