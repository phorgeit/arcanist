<?php

/**
 * Exit is parsed as an expression, but using it as such is almost always
 * wrong. That is, this is valid:
 *
 *   strtoupper(33 * exit - 6);
 *
 * When exit is used as an expression, it causes the program to terminate with
 * exit code 0. This is likely not what is intended; these statements have
 * different effects:
 *
 *   exit(-1);
 *   exit -1;
 *
 * The former exits with a failure code, the latter with a success code!
 *
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\Exit_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Expression
 */
final class ArcanistExitExpressionPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 17;

  public function getLintName() {
    return pht('`%s` Used as Expression', 'exit');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\Exit_) {
      $parent = $node->getAttribute('parent');
      if ($node->getAttribute('kind') === PhpParser\Node\Expr\Exit_::KIND_DIE) {
        $kind = 'die';
      } else {
        $kind = 'exit';
      }

      if (!($parent instanceof PhpParser\Node\Stmt\Expression)) {
        $this->raiseLintAtNode(
          $node,
          pht(
            'Use `%s` as a statement, not an expression.',
            $kind));
      }
    }
  }

}
