<?php

/**
 * @phutil-external-symbol class PhpParser\Modifiers
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Stmt\Property
 */
final class ArcanistImplicitVisibilityPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 52;

  public function getLintName() {
    return pht('Implicit Method Visibility');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
      if (
        !($node->flags & PhpParser\Modifiers::PUBLIC) &&
        !($node->flags & PhpParser\Modifiers::PROTECTED) &&
        !($node->flags & PhpParser\Modifiers::PRIVATE)) {

        $this->raiseLintAtNode(
          $node,
          pht('Methods should have their visibility declared explicitly.'),
          'public '.$this->getString($node, $token_stream),
          $token_stream);
      }
    } else if ($node instanceof PhpParser\Node\Stmt\Property) {
      if (
        !($node->flags & PhpParser\Modifiers::PUBLIC) &&
        !($node->flags & PhpParser\Modifiers::PROTECTED) &&
        !($node->flags & PhpParser\Modifiers::PRIVATE)) {

        $token_range = array_slice(
          $token_stream,
          $node->getStartTokenPos(),
          head($node->props)->getStartTokenPos());

        foreach ($token_range as $token) {
          if ($token->is(T_VAR)) {
            $this->raiseLintAtToken(
              $token,
              pht(
                'Use `%s` instead of `%s` to indicate public visibility.',
                'public',
                'var'),
              'public');

            return;
          }
        }

        $this->raiseLintAtNode(
          $node,
          pht('Properties should have their visibility declared explicitly.'),
          'public '.$this->getString($node, $token_stream),
          $token_stream);
      }
    }
  }

}
