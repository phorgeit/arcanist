<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\List_
 */
final class ArcanistListAssignmentPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 77;

  public function getLintName() {
    return pht('List Assignment');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\List_ && $node->items) {
      $items = array_reverse($node->items);
      $end_token = $node->getEndTokenPos();
      $last_valid_token = -1;

      foreach ($items as $item) {
        if ($item !== null) {
          $last_valid_token = $item->getEndTokenPos();
          break;
        }
      }

      if ($last_valid_token === -1) {
        return;
      }

      for ($i = $last_valid_token; $i < $end_token; $i++) {
        if ($token_stream[$i]->is(',')) {
          $this->raiseLintAtToken(
            $token_stream[$i],
            pht('Unnecessary comma in list assignment.'),
            '');
        }
      }
    }
  }

}
