<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Property
 */
final class ArcanistPublicPropertyPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 130;

  public function getLintName() {
    return pht('Use of `%s` Properties', 'public');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Stmt\Property &&
      !$node->hooks &&
      !$node->isReadonly() &&
      $node->isPublic()) {

      $this->raiseLintAtNode(
        $node,
        pht(
          '`%s` properties should be avoided. Instead of exposing '.
          'the property value directly, consider using getter '.
          'and setter methods.',
          'public'));
    }
  }

}
