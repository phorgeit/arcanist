<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 */
final class ArcanistImplicitConstructorPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 10;

  public function getLintName() {
    return pht('Implicit Constructor');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    $version = $this->version;

    if (!$version) {
      $version = PHP_VERSION;
    }

    // PHP 8.0 no longer considers these methods constructors.
    // Namespaced classes never considered these method constructors.
    if (version_compare($version, '8.0.0', '>=')) {
      return;
    }

    if (
      $node instanceof PhpParser\Node\Stmt\Class_ &&
      $node->name &&
      $node->namespacedName->toString() === $node->name->toString()) {

      foreach ($node->getMethods() as $method) {
        if ($node->name->toLowerString() === $method->name->toLowerString()) {
          $this->raiseLintAtNode(
            $method->name,
            pht(
              'Name constructors `%s` explicitly. This method is a '.
              'constructor because it has the same name as the class '.
              'it is defined in.',
              '__construct'));
        }
      }
    }
  }

}
