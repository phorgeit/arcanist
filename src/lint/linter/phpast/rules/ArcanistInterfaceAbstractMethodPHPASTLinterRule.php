<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Stmt\Interface_
 */
final class ArcanistInterfaceAbstractMethodPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 118;

  public function getLintName() {
    return pht('`%s` Methods Cannot Be Marked `%s`', 'interface', 'abstract');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\Interface_) {
      foreach ($node->stmts as $stmt) {
        if (
          $stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
          $stmt->isAbstract()) {

          $this->raiseLintAtNode(
            $stmt,
            pht(
              '`%s` methods cannot be marked as `%s`. This construct will '.
              'cause a fatal error.',
              'interface',
              'abstract'));
        }
      }
    }
  }

}
