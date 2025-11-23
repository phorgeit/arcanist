<?php

final class ArcanistPaamayimNekudotayimSpacingPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 96;

  public function getLintName() {
    return pht('Paamayim Nekudotayim Spacing');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    foreach ($token_stream as $i => $token) {
      if ($token->is(T_PAAMAYIM_NEKUDOTAYIM)) {
        $non_semantic_tokens = array_merge(
          $this->getNonsemanticTokensBefore($i, $token_stream),
          $this->getNonsemanticTokensAfter($i, $token_stream));

        foreach ($non_semantic_tokens as $non_semantic_token) {
          if ($non_semantic_token->is(T_WHITESPACE)) {
            if (strpos($non_semantic_token->text, "\n") !== false) {
              continue;
            }

            $this->raiseLintAtToken(
              $non_semantic_token,
              pht(
                'Unnecessary whitespace around paamayim nekudotayim '.
                '(double colon) operator.'),
              '');
          }
        }
      }
    }
  }

}
