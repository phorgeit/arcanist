<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 */
final class ArcanistAbstractPrivateMethodPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 107;

  public function getLintName() {
    return pht('`%s` Method Cannot Be Declared `%s`', 'abstract', 'private');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Stmt\ClassMethod &&
      $node->isAbstract() &&
      $node->isPrivate()) {

      $this->raiseLintAtNode(
        $node,
        pht(
          '`%s` method cannot be declared `%s`. '.
          'This construct will cause a fatal error.',
          'abstract',
          'private'));
    }
  }

}
