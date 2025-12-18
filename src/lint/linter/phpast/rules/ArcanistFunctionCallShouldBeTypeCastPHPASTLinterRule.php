<?php

/**
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp
 * @phutil-external-symbol class PhpParser\Node\Expr\Ternary
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Arg
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Scalar\Int_
 */
final class ArcanistFunctionCallShouldBeTypeCastPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 105;

  public function getLintName() {
    return pht('Function Call Should Be Type Cast');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      (!$node instanceof PhpParser\Node\Expr\FuncCall) ||
      !($node->name instanceof PhpParser\Node\Name)) {

      return;
    }

    static $cast_functions;

    if ($cast_functions === null) {
      $cast_functions = array(
        'boolval' => 'bool',
        'doubleval' => 'double',
        'floatval' => 'double',
        'intval' => 'int',
        'strval' => 'string',
      );
    }

    $name = $node->name->toLowerString();
    $cast_name = idx($cast_functions, $name);

    if (!$cast_name) {
      return;
    }

    $replacement = null;

    // Only suggest a replacement if the function call has exactly
    // one parameter.
    if (count($node->args) === 1) {
      $replacement = $this->furnishReplacement(
        $node->args[0],
        $cast_name,
        $token_stream);
    }

    if ($name === 'intval' && count($node->args) >= 2) {
      if (
        !($node->args[1]->value instanceof PhpParser\Node\Scalar\Int_) ||
        $node->args[1]->value->value !== 10) {
        return;
      }

      $replacement = $this->furnishReplacement(
        $node->args[0],
        $cast_name,
        $token_stream);
    }

    $this->raiseLintAtNode(
      $node,
      pht(
        'For consistency, use `%s` (a type cast) instead of `%s` '.
        '(a function call). Function calls impose additional overhead.',
        '('.$cast_name.')',
        $name),
      $replacement,
      $token_stream);
  }

  /**
   * @param PhpParser\Node\Arg $arg
   * @param string $cast_name
   * @param array<PhpParser\Token> $token_stream
   * @return string
   */
  private function furnishReplacement(
    PhpParser\Node\Arg $arg,
    string $cast_name,
    array $token_stream) {

    $replacement = '('.$cast_name.')';
    $argument = $this->getString($arg, $token_stream);

    if (
      $arg->value instanceof PhpParser\Node\Expr\BinaryOp ||
      $arg->value instanceof PhpParser\Node\Expr\Ternary) {

      return $replacement.'('.$argument.')';
    } else {
      return $replacement.$argument;
    }
  }

}
