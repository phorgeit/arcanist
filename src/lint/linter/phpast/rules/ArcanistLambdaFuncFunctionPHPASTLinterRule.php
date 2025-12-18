<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 * @phutil-external-symbol class PhpParser\Node\Name
 */
final class ArcanistLambdaFuncFunctionPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 68;

  public function getLintName() {
    return pht('`%s` Function', '__lambda_func');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Stmt\Function_ &&
      $node->namespacedName->toLowerString() === '__lambda_func') {

      $this->raiseLintAtNode(
        $node,
        pht(
          'Declaring a function named `%s` causes any call to %s to fail. '.
          'This is because `%s` eval-declares the function `%s`, then '.
          'modifies the symbol table so that the function is instead '.
          'named `%s`, and returns that name.',
          '__lambda_func',
          'create_function',
          'create_function',
          '__lambda_func',
          '"\0lambda_".(++$i)'));
    }
  }

}
