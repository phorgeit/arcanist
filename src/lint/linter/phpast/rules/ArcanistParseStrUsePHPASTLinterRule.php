<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Name
 */
final class ArcanistParseStrUsePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 80;

  public function getLintName() {
    return pht('Questionable Use of `%s`', 'parse_str');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name &&
      $node->name->toLowerString() === 'parse_str' &&
      count($node->args) < 2) {

      $this->raiseLintAtNode(
        $node,
        pht(
          'Avoid `%s` unless the second parameter is specified. '.
          'It is confusing and hinders static analysis.',
          'parse_str'));
    }
  }

}
