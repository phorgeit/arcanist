<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Stmt\Property
 */
final class ArcanistModifierOrderingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 71;

  public function getLintName() {
    return pht('Modifier Ordering');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    static $modifiers = array(
      'abstract',
      'final',
      'public',
      'public(set)',
      'protected',
      'protected(set)',
      'private',
      'private(set)',
      'static',
      'readonly',
    );

    if (
      !($node instanceof PhpParser\Node\Stmt\ClassMethod) &&
      !($node instanceof PhpParser\Node\Stmt\Property)) {
      return;
    }

    list($modifier_ordering, $modifiers_end) = $this->extractModifiersInOrder(
      $node,
      $token_stream);
    $expected_modifier_ordering = array_values(
      array_intersect(
        $modifiers,
        $modifier_ordering));

    $original = '';
    for ($i = $node->getStartTokenPos(); $i < $modifiers_end; $i++) {
      $original .= $token_stream[$i]->text;
    }

    if ($modifier_ordering !== $expected_modifier_ordering) {
      $this->raiseLintAtOffset(
        $node->getStartFilePos(),
        pht('Non-conventional modifier ordering.'),
        $original,
        implode(' ', $expected_modifier_ordering));
    }
  }

  private function extractModifiersInOrder(
    PhpParser\Node $node,
    array $token_stream) {

    $modifiers = array();
    $offset = 0;

    for ($i = $node->getStartTokenPos(); $i < $node->getEndTokenPos(); $i++) {
      $token = $token_stream[$i];
      if (
        $token->id === T_ABSTRACT ||
        $token->id === T_STATIC ||
        $token->id === T_PUBLIC ||
        $token->id === T_PROTECTED ||
        $token->id === T_PRIVATE ||
        $token->id === T_FINAL ||
        $token->id === T_PUBLIC_SET ||
        $token->id === T_PROTECTED_SET ||
        $token->id === T_PRIVATE_SET ||
        $token->id === T_READONLY) {
        $modifiers[] = $token->text;
      } else if ($token->isIgnorable()) {
        continue;
      } else {
        $offset = $i - 1;
        break;
      }
    }

    return array($modifiers, $offset);
  }

}
