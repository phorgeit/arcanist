<?php

/**
 * Lint that if the file declares exactly one interface or class, the name of
 * the file matches the name of the class, unless the class name is funky like
 * an XHP element.
 *
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassLike
 */
final class ArcanistClassFilenameMismatchPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 19;

  public function getLintName() {
    return pht('Class-Filename Mismatch');
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    $types = $ast->findNodesOfKind(PhpParser\Node\Stmt\ClassLike::class);

    if (count($types) !== 1) {
      return;
    }

    $type = head($types);
    $name = $type->name->toString();

    // Exclude strangely named classes, e.g. XHP tags.
    if (!preg_match('/^\w+$/', $name)) {
      return;
    }

    $rename = $name.'.php';

    $path = $this->getActivePath();
    $filename = basename($path);

    if ($rename === $filename) {
      return;
    }

    $this->raiseLintAtNode(
      $type,
      pht(
        'The name of this file differs from the name of the '.
        'class, interface, trait or enum it declares. Rename the file '.
        'to `%s`.',
        $rename));
  }

}
