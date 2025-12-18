<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Name
 */
final class ArcanistExtractUsePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 4;

  public function getLintName() {
    return pht('Use of `%s`', 'extract');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name &&
      $node->name->toLowerString() === 'extract') {

      $this->raiseLintAtNode(
        $node,
        pht(
          'Avoid `%s`. It is confusing and hinders static analysis.',
          'extract'));
    }
  }

}
