<?php

/**
 * @phutil-external-symbol class PhpParser\NodeVisitorAbstract
 * @phutil-external-symbol class PhpParser\Node
 */
final class PHPASTDocBlockVisitor extends PhpParser\NodeVisitorAbstract {

  /**
   * @var PhpParser\Comment\Doc[]
   */
  private $docBlocks = array();

  public function getDocBlocks() {
    return $this->docBlocks;
  }

  public function enterNode(PhpParser\Node $node) {
    if ($node->getDocComment()) {
      $this->docBlocks[] = $node->getDocComment();
    }

    return null;
  }

}
