<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\LogicalOr
 */
final class ArcanistLowercaseFunctionsPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 61;

  public function getLintName() {
    return pht('Lowercase Functions');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    static $builtin_functions = null;

    if ($builtin_functions === null) {
      $builtin_functions = array_fuse(
        idx(get_defined_functions(), 'internal', array()));
    }

    if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name) {

      if (!idx($builtin_functions, $node->name->toLowerString())) {
        return;
      }

      if ($node->name->toLowerString() !== $node->name->toString()) {
        $this->raiseLintAtNode(
          $node->name,
          pht('Calls to built-in PHP functions should be lowercase.'),
          $node->name->toLowerString(),
        $token_stream);
      }
    }
  }

}
