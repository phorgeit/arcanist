<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Stmt\Return_
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 * @phutil-external-symbol class PhpParser\Node\PropertyHook
 */
final class ArcanistUnexpectedReturnValuePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 92;

  public function getLintName() {
    return pht('Unexpected `%s` Value', 'return');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
      $name = $node->name->toLowerString();

      if ($name !== '__construct' && $name !== '__destruct') {
        return;
      }

      if (!$node->stmts) {
        return;
      }

      $returns = PhpParserAst::newPartialAst($node->stmts)
        ->findNodesOfKind(
          PhpParser\Node\Stmt\Return_::class,
          array(PhpParser\Node\FunctionLike::class));

      foreach ($returns as $return) {
        if ($return->expr) {
          $this->raiseLintAtNode(
            $return,
            pht(
              'Unexpected `%s` value in `%s` method.',
              'return',
              $node->name->toString()));
        }
      }
    } else if (
      $node instanceof PhpParser\Node\PropertyHook &&
      $node->name->toLowerString() === 'set') {

      if (!$node->body) {
        return;
      }

      $returns = PhpParserAst::newPartialAst($node->body)
        ->findNodesOfKind(
          PhpParser\Node\Stmt\Return_::class,
          array(PhpParser\Node\FunctionLike::class));

      foreach ($returns as $return) {
        if ($return->expr) {
          $this->raiseLintAtNode(
            $return,
            pht(
              'Unexpected `%s` value in property hook setter.',
              'return'));
        }
      }
    }
  }

}
