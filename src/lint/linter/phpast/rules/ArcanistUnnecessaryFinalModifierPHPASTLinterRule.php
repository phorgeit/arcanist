<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 */
final class ArcanistUnnecessaryFinalModifierPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 55;

  public function getLintName() {
    return pht('Unnecessary Final Modifier');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (!($node instanceof PhpParser\Node\Stmt\Class_)) {
      return;
    }

    if (!$node->name) {
      return;
    }

    if (!$node->isFinal()) {
      return;
    }

    foreach ($node->getMethods() as $method) {
      if ($method->isFinal()) {
        $this->raiseLintAtNode(
          $method,
          pht(
            'Unnecessary `%s` modifier in `%s` class.',
            'final',
            'final'));
      }
    }
  }

}
