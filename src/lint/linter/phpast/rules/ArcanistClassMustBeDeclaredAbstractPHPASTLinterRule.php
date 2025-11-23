<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 */
final class ArcanistClassMustBeDeclaredAbstractPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 113;

  public function getLintName() {
    return pht(
      '`%s` Containing `%s` Methods Must Be Declared `%s`',
      'class',
      'abstract',
      'abstract');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\Class_ && !$node->isAbstract()) {
      $abstract_methods = mfilter($node->getMethods(), 'isAbstract');

      if ($abstract_methods) {
        $this->raiseLintAtOffset(
          $node->getStartFilePos(),
          pht(
            'Class contains %s %s method(s) and must therefore '.
            'be declared `%s`.',
            phutil_count($abstract_methods),
            'abstract',
            'abstract'),
          'class',
          'abstract class');
      }
    }
  }

}
