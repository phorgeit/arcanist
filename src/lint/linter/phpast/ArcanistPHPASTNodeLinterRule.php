<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 */
abstract class ArcanistPHPASTNodeLinterRule extends ArcanistPHPASTLinterRule {

  /**
   * @param PhpParser\Node $node
   * @param array<PhpParser\Token> $token_stream
   */
  abstract public function process(PhpParser\Node $node, array $token_stream);

}
