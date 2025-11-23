<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Switch_
 */
final class ArcanistDuplicateSwitchCasePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 50;

  public function getLintName() {
    return pht('Duplicate Case Statements');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\Switch_) {
      $nodes_by_case = array();

      foreach ($node->cases as $case) {
        $label = $this->getSemanticString($case, $token_stream);
        $nodes_by_case[$label][] = $case;
      }

      foreach ($nodes_by_case as $case => $nodes) {
        if (count($nodes) <= 1) {
          continue;
        }

        $node = array_pop($nodes_by_case[$case]);
        $message = $this->raiseLintAtNode(
          $node,
          pht(
            'Duplicate case in switch statement. PHP will ignore all '.
            'but the first case.'));

        $locations = array();
        foreach ($nodes_by_case[$case] as $other_node) {
          $locations[] = $this->getOtherLocation(
            $other_node->getStartFilePos());
        }
        $message->setOtherLocations($locations);
      }
    }
  }

}
