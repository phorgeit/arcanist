<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 */
final class ArcanistStaticThisPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 13;

  public function getLintName() {
    return pht('Use of `%s` in Static Context', '$this');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (!($node instanceof PhpParser\Node\Stmt\Class_)) {
      return;
    }

    foreach ($node->getMethods() as $method) {
      if ($method->isAbstract() || !$method->isStatic()) {
        continue;
      }

      $variables = PhpParserAst::newPartialAst($method->stmts)
        ->findNodesOfKind(PhpParser\Node\Expr\Variable::class);

      foreach ($variables as $variable) {
        if (
          is_string($variable->name) &&
          strtolower($variable->name) === 'this') {
            $this->raiseLintAtNode(
              $variable,
              pht(
                'You can not reference `%s` inside a static method.',
                '$this'));
          }
      }
    }
  }

}
