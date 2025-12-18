<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 */
final class ArcanistClassExtendsObjectPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 88;

  public function getLintName() {
    return pht('Class Not Extending `%s`', Phobject::class);
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_DISABLED;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Stmt\Class_ &&
      !$node->extends &&
      (!$node->name || $node->name->toString() !== 'Phobject')) {

      $this->raiseLintAtNode(
        $node,
        pht(
          'Classes should extend from `%s` or from some other class. '.
          'All classes (except for `%s` itself) should have a base class.',
          Phobject::class,
          Phobject::class));
    }
  }

}
