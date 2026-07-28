<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Expr
 * @phutil-external-symbol class PhpParser\Node\Stmt\Return_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Expression
 * @phutil-external-symbol class PhpParser\Node\Expr\YieldFrom
 * @phutil-external-symbol class PhpParser\Node\Expr\StaticCall
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 */
final class ArcanistUselessOverridingMethodPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 63;

  public function getLintName() {
    return pht('Useless Overriding Method');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
      if (!$node->stmts || count($node->stmts) > 1) {
        return;
      }

      list($stmt) = $node->stmts;

      $parameters = array();

      foreach ($node->params as $param) {
        if (
          $param->default ||
          $param->isPromoted() ||
          $param->var->name instanceof PhpParser\Node\Expr) {

          continue;
        }

        $parameters[] = $param->var->name;
      }

      $expression = null;

      if (
        $stmt instanceof PhpParser\Node\Stmt\Return_ ||
        $stmt instanceof PhpParser\Node\Stmt\Expression) {
        $expression = $stmt->expr;
      }

      if ($expression instanceof PhpParser\Node\Expr\YieldFrom) {
        $expression = $expression->expr;
      }

      if (!($expression instanceof PhpParser\Node\Expr\StaticCall)) {
         return;
      }

      /** @var PhpParser\Node\Expr\StaticCall $expression */
      if (
        $expression->class->name !== 'parent' ||
        $expression->name->toString() !== $node->name->name) {
        return;
      }

      foreach ($expression->args as $arg) {
        if (!($arg->value instanceof PhpParser\Node\Expr\Variable)) {
          return;
        }

        $expected = array_shift($parameters);

        if ($arg->value->name !== $expected) {
          return;
        }
      }

      $this->raiseLintAtNode(
        $node,
        pht('Useless overriding method.'));
    }
  }

}
