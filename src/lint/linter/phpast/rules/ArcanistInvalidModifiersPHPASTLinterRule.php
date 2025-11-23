<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Stmt\Property
 */
final class ArcanistInvalidModifiersPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 72;

  public function getLintName() {
    return pht('Invalid Modifiers');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
      if ($node->isPrivate() && $node->isFinal()) {
        $this->raiseLintAtNode(
          $node,
          pht('Methods may not be both "private" and "final".'));
      }
    } else if ($node instanceof PhpParser\Node\Stmt\Property) {
      if ($node->isAbstract()) {
        $this->raiseLintAtNode(
          $node,
          pht('Properties cannot be declared "abstract".'));
      }
    }
  }

}
