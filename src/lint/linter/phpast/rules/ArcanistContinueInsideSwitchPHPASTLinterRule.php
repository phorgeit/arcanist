<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Break_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Continue_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Do_
 * @phutil-external-symbol class PhpParser\Node\Stmt\For_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Foreach_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Switch_
 * @phutil-external-symbol class PhpParser\Node\Stmt\While_
 */
final class ArcanistContinueInsideSwitchPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 128;

  public function getLintName() {
    return pht('Continue Inside Switch');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\Switch_) {
      $continues = PhpParserAst::newPartialAst($node->cases)
        ->findNodesOfKind(
          PhpParser\Node\Stmt\Continue_::class,
          array(
            PhpParser\Node\Stmt\Do_::class,
            PhpParser\Node\Stmt\For_::class,
            PhpParser\Node\Stmt\Foreach_::class,
            PhpParser\Node\Stmt\While_::class,
          ));

      foreach ($continues as $continue) {
        // If this is a "continue 2;" or similar, assume it's legitimate.
        if ($continue->num) {
          continue;
        }

        $trailing = $this->getNonsemanticTokensAfter(
          $continue->getStartTokenPos(),
          $token_stream);
        $trailing_text = implode('', ppull($trailing, 'text'));

        $replacement = 'break'.$trailing_text.';';

        $this->raiseLintAtNode(
          $continue,
          pht(
            'In a "switch" statement, "continue;" is equivalent to "break;" '.
            'but causes compile errors beginning with PHP 7.0.0.'),
          $replacement,
          $token_stream);
      }
    }
  }

}
