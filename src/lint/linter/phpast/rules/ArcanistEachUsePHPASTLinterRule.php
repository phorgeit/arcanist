<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Name
 */
final class ArcanistEachUsePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 133;

  public function getLintName() {
    return pht('Use of Removed Function "each()"');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name &&
      $node->name->toLowerString() === 'each') {

      $this->raiseLintAtNode(
        $node,
        pht(
          'Do not use "each()". This function was deprecated in PHP 7.2 '.
          'and removed in PHP 8.0'));
    }
  }

}
