<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 */
final class ArcanistAbstractMethodBodyPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 108;

  public function getLintName() {
    return pht('`%s` Method Cannot Contain Body', 'abstract');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Stmt\ClassMethod &&
      $node->isAbstract() &&
      $node->stmts !== null) {

      $this->raiseLintAtNode(
        $node,
        pht(
          '`%s` methods cannot contain a body. This construct will '.
          'cause a fatal error.',
          'abstract'));
    }
  }

}
