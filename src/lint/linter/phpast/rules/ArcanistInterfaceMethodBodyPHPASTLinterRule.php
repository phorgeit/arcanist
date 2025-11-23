<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Interface_
 */
final class ArcanistInterfaceMethodBodyPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 114;

  public function getLintName() {
    return pht('`%s` Method Cannot Contain Body', 'interface');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\Interface_) {
      foreach ($node->getMethods() as $method) {
        if ($method->stmts !== null) {
          $this->raiseLintAtNode(
            $method->stmts ? head($method->stmts) : $method,
            pht(
              '`%s` methods cannot contain a body. This construct will '.
              'cause a fatal error.',
              'interface'));
        }
      }
    }
  }

}
