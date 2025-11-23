<?php

final class ArcanistSemicolonSpacingPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 43;

  public function getLintName() {
    return pht('Semicolon Spacing');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    foreach ($token_stream as $i => $token) {
      if ($token->is(';') && $token_stream[$i - 1]->is(T_WHITESPACE)) {
        $this->raiseLintAtToken(
          $token_stream[$i - 1],
          pht('Space found before semicolon.'),
          '');
      }
    }
  }

}
