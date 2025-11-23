<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassLike
 */
final class ArcanistSelfMemberReferencePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 57;

  public function getLintName() {
    return pht('Self Member Reference');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (!$this->version) {
      $version_target = PHP_VERSION;
    } else {
      $version_target = $this->version;
    }

    if ($node instanceof PhpParser\Node\Stmt\ClassLike && $node->name) {
      $name = $node->namespacedName->toLowerString();
      $static_accesses = PhpParserAst::newPartialAst($node->getMethods())
        ->findStaticAccess();

      foreach ($static_accesses as $static_access_node => $in_closure) {
        if (
          $static_access_node->class->toLowerString() !== $name) {
          continue;
        }

        if (
          version_compare($version_target, '5.4.0', '>=') ||
          !$in_closure) {
            $this->raiseLintAtNode(
              $static_access_node->class,
              pht(
                'Use `%s` for local static member references.',
                'self::'),
              'self',
              $token_stream);
          }
      }
    }
  }

}
