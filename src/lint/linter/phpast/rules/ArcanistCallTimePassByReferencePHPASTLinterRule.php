<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Arg
 */
final class ArcanistCallTimePassByReferencePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 53;

  public function getLintName() {
    return pht('Call-Time Pass-By-Reference');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Arg && $node->byRef) {
      $this->raiseLintAtNode(
        $node,
        pht('Call-time pass-by-reference calls are prohibited.'));
    }
  }

}
