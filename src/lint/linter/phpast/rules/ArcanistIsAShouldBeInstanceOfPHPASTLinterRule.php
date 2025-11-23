<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\ConstFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 * @phutil-external-symbol class PhpParser\Node\Identifier
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Expr\ClassConstFetch
 */
final class ArcanistIsAShouldBeInstanceOfPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 111;

  public function getLintName() {
    return pht('`%s` Should Be `%s`', 'is_a', 'instanceof');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name &&
      $node->name->toLowerString() === 'is_a') {

      if (count($node->args) > 2) {
        // If the `$allow_string` parameter is `true` then the `instanceof`
        // operator cannot be used. Evaluating whether an expression is truthy
        // or falsely is hard, and so we only check that the `$allow_string`
        // parameter is either absent or literally `false`.

        $allow_string = $node->args[2];

        if (
          !($allow_string instanceof PhpParser\Node\Expr\ConstFetch) ||
          $allow_string->name->toLowerString() !== 'false') {
          return;
        }
      }

      list($object, $class) = $node->args;

      if ($class->value instanceof PhpParser\Node\Scalar\String_) {
        $replacement = $class->value->value;
      } else if (
        $class->value instanceof PhpParser\Node\Expr\Variable &&
        is_string($class->value->name)) {
        $replacement = '$'.$class->value->name;
      } else if (
        $class->value instanceof PhpParser\Node\Expr\ClassConstFetch &&
        $class->value->class instanceof PhpParser\Node\Name &&
        $class->value->name instanceof PhpParser\Node\Identifier &&
        $class->value->name->name === 'class') {

        $replacement = $class->class->toCodeString();
      } else {
        $replacement = null;
      }

      $this->raiseLintAtNode(
        $node,
        pht(
          'Use `%s` instead of `%s`. The former is a language '.
          'construct whereas the latter is a function call, which '.
          'has additional overhead.',
          'instanceof',
          'is_a'),
        ($replacement === null)
          ? null
          : sprintf(
            '%s instanceof %s',
            $this->getSemanticString($object, $token_stream),
            $replacement),
        $token_stream);
    }
  }

}
