<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\ElseIf_
 */
final class ArcanistElseIfUsagePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 42;

  public function getLintName() {
    return pht('`elseif` Usage');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\ElseIf_) {
      $this->raiseLintAtToken(
        $token_stream[$node->getStartTokenPos()],
        pht(
          'Usage of `%s` is preferred over `%s`.',
          'else if',
          'elseif'),
        'else if');
    }
  }

}
