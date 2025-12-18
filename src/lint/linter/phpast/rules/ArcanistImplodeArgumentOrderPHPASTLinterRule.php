<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Scalar\Int_
 * @phutil-external-symbol class PhpParser\Node\Scalar\Float_
 * @phutil-external-symbol class PhpParser\Node\Expr\ConstFetch
 */
final class ArcanistImplodeArgumentOrderPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 129;

  public function getLintName() {
    return pht('Implode With Glue First');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ERROR;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\FuncCall) {
      if (
        !($node->name instanceof PhpParser\Node\Name) ||
        $node->name->toLowerString() !== 'implode' ||
        count($node->args) !== 2) {

        return;
      }

      list(, $arg) = $node->args;

      // If the value is a static scalar, like a string literal, or a constant,
      // like "DIRECTORY_SEPARATOR", it's probably the glue.
      if (
        $arg->value instanceof PhpParser\Node\Scalar\String_ ||
        $arg->value instanceof PhpParser\Node\Scalar\Int_ ||
        $arg->value instanceof PhpParser\Node\Scalar\Float_ ||
        $arg->value instanceof PhpParser\Node\Expr\ConstFetch) {

        $this->raiseLintAtNode(
          $node,
          pht(
            'When calling "implode()", pass the "glue" argument first. (The '.
            'other parameter order is deprecated in PHP 7.4 and raises a '.
            'warning.)'));
      }
    }
  }

}
