<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\New_
 * @phutil-external-symbol class PhpParser\Node\Name
 */
final class ArcanistConstructorParenthesesPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 49;

  public function getLintName() {
    return pht('Constructor Parentheses');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\New_ &&
      !$node->args) {

      if ($node->class instanceof PhpParser\Node\Name) {
        $last_token = $token_stream[$node->getEndTokenPos()];
        if (!$last_token->is(')')) {
          $this->raiseLintAtNode(
            $node->class,
            pht('Use parentheses when invoking a constructor.'),
            $node->class->toString().'()',
            $token_stream);
        }
      } else {
        $class_end = $node->class->getEndTokenPos();
        $node_end = $node->getEndTokenPos();

        if ($class_end + 1 === $node_end) {
          $this->raiseLintAtNode(
            $node,
            pht('Use parentheses when invoking a constructor.'),
            $this->getString($node, $token_stream).'()',
            $token_stream);
        }
      }
    }
  }

}
