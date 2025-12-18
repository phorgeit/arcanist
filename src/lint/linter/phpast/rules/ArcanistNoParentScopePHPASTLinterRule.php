<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 */
final class ArcanistNoParentScopePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 64;

  public function getLintName() {
    return pht('No Parent Scope');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Stmt\Class_ &&
      !$node->extends) {

      $static_accesses = PhpParserAst::newPartialAst($node->getMethods())
        ->findStaticAccess();

      foreach ($static_accesses as $static_access_node => $in_closure) {
        if (
          $static_access_node->class instanceof PhpParser\Node\Name &&
          $static_access_node->class->toLowerString() === 'parent') {
          $this->raiseLintAtNode(
            $static_access_node,
            pht(
              'Cannot access `%s` when current class scope has no parent.',
              'parent::'));
        }
      }
    }
  }

}
