<?php

final class ArcanistCommaSpacingPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 135;

  public function getLintName() {
    return pht('Space Around Comma');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    foreach ($token_stream as $i => $token) {
      if (!$token->is(',') || $token->is(T_ENCAPSED_AND_WHITESPACE)) {
        continue;
      }

      if ($i < count($token_stream)) {
        $next = $token_stream[$i + 1];
        if ($next->is(array(')', T_WHITESPACE))) {
          continue;
        }

        $this->raiseLintAtToken(
          $token,
          pht('Convention: comma should be followed by space.'),
          ', ');
      }

    }
  }

}
