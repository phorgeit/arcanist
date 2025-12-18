<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\UseItem
 */
final class ArcanistUnnecessarySymbolAliasPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 99;

  public function getLintName() {
    return pht('Unnecessary Symbol Alias');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\UseItem) {
      if (!$node->alias) {
        return;
      }

      if ($node->name->getLast() === $node->alias->name) {
        $this->raiseLintAtNode(
          $node,
          pht(
            'Importing `%s` with `%s` is unnecessary because the aliased '.
            'name is identical to the imported symbol name.',
            $node->name->toCodeString(),
            sprintf('as %s', $node->alias->name)),
          $node->name->toString(),
          $token_stream);
      }
    }
  }

}
