<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 */
final class ArcanistRaggedClassTreeEdgePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 87;

  public function getLintName() {
    return pht('Class Not `%s` Or `%s`', 'abstract', 'final');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_DISABLED;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    $parser = new PhutilDocblockParser();

    if ($node instanceof PhpParser\Node\Stmt\Class_) {
      if (!$node->name || $node->isAbstract() || $node->isFinal()) {
        return;
      }

      $docblock = $node->getDocComment();
      $is_concrete_extensible = false;

      if ($docblock) {
        list($text, $specials) = $parser->parse($docblock->getText());
        $is_concrete_extensible = idx($specials, 'concrete-extensible');
      }

      if (!$is_concrete_extensible) {
        $this->raiseLintAtNode(
          $node->name,
          pht(
            'This class is neither `%s` nor `%s`, and does not have '.
            'a docblock marking it `%s`.',
            'final',
            'abstract',
            '@concrete-extensible'));
      }
    }
  }

}
