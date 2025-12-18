<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\TryCatch
 */
final class ArcanistPartialCatchPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 132;

  public function getLintName() {
    return pht('Partial Catch');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\TryCatch) {
      $classes = array();

      foreach ($node->catches as $catch) {
        foreach ($catch->types as $type) {
          $classes[$type->toLowerString()] = $type;
        }
      }

      $catches_exception = idx($classes, 'exception');
      $catches_throwable = idx($classes, 'throwable');

      if ($catches_exception && !$catches_throwable) {
        $this->raiseLintAtNode(
          $catches_exception,
          pht(
            'Try/catch block catches "Exception", but does not catch '.
            '"Throwable". In PHP7 and newer, some runtime exceptions '.
            'will escape this block.'));
      }
    }
  }

}
