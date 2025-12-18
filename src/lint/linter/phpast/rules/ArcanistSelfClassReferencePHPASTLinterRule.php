<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Expr\New_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassLike
 */
final class ArcanistSelfClassReferencePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 95;

  public function getLintName() {
    return pht('Self Class Reference');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Stmt\ClassLike &&
      $node->name) {

      $name = $node->namespacedName->toLowerString();
      $class_instantiations = PhpParserAst::newPartialAst($node->getMethods())
        ->findNodesOfKind(PhpParser\Node\Expr\New_::class);

      foreach ($class_instantiations as $instantiation) {
        if (
          !($instantiation->class instanceof PhpParser\Node\Name) ||
          $instantiation->class->toLowerString() !== $name) {
          continue;
        }

        $this->raiseLintAtNode(
          $instantiation->class,
          pht(
            'Use `%s` to instantiate the current class.',
            'self'),
          'self',
          $token_stream);
      }
    }
  }

}
