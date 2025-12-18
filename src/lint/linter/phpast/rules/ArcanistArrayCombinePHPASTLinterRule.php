<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Name
 */
final class ArcanistArrayCombinePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 84;

  public function getLintName() {
    return pht('`%s` Unreliable', 'array_combine()');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_DISABLED;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name &&
      $node->name->toLowerString() === 'array_combine' &&
      count($node->args) === 2) {

      list($first, $second) = $node->args;
      $first = $this->getString($first, $token_stream);
      $second = $this->getString($second, $token_stream);

      if ($first === $second) {
        $this->raiseLintAtNode(
          $node,
          pht(
            'Prior to PHP 5.4, `%s` fails when given empty arrays. '.
            'Prefer to write `%s` as `%s`.',
            'array_combine()',
            'array_combine($x, $x)',
            'array_fuse($x)'));
      }
    }
  }

}
