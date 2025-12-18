<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 */
final class ArcanistInnerFunctionPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 59;

  public function getLintName() {
    return pht('Inner Functions');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\FunctionLike && $node->getStmts()) {
      $inner_functions = PhpParserAst::newPartialAst($node->getStmts())
        ->findNodesOfKind(PhpParser\Node\Stmt\Function_::class);

      foreach ($inner_functions as $inner_function) {
        $this->raiseLintAtNode(
          $inner_function,
          pht('Avoid the use of inner functions.'));
      }
    }
  }

}
