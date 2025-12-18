<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Use_
 * @phutil-external-symbol class PhpParser\Node\Stmt\GroupUse
 */
final class ArcanistUseStatementNamespacePrefixPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 97;

  public function getLintName() {
    return pht('`%s` Statement Namespace Prefix', 'use');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Stmt\Use_ ||
      $node instanceof PhpParser\Node\Stmt\GroupUse) {

      foreach ($node->uses as $use) {
        // PHP-Parser consumes any leading backslash in use statements
        // completely.
        if (
          $token_stream[$use->getStartTokenPos()]->is(T_NAME_FULLY_QUALIFIED)) {

          $this->raiseLintAtToken(
            $token_stream[$use->getStartTokenPos()],
            pht(
              'Imported symbols should not be prefixed with `%s`.',
              '\\'),
            $use->name->toCodeString());
        }
      }
    }
  }

}
