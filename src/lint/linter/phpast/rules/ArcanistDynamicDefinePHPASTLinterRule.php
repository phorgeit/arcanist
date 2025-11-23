<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 */
final class ArcanistDynamicDefinePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 12;

  public function getLintName() {
    return pht('Dynamic `%s`', 'define');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name &&
      $node->name->toLowerString() === 'define' &&
      $node->args) {

      $defined = $node->args[0]->value;
      $kind = $defined->getAttribute('kind');

      if (
        !($defined instanceof PhpParser\Node\Scalar\String_) ||
        $kind === PhpParser\Node\Scalar\String_::KIND_NOWDOC ||
        $kind === PhpParser\Node\Scalar\String_::KIND_HEREDOC) {

        $this->raiseLintAtNode(
          $defined,
          pht(
            'First argument to `%s` must be a string literal.',
            'define'));
      }
    }
  }

}
