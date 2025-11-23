<?php

final class ArcanistEmptyFilePHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 82;

  public function getLintName() {
    return pht('Empty File');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    foreach ($token_stream as $token) {
      // Unlike the regular tokenizer, PHP-Parser terminates the token stream
      // with a sentinel token that has an ID of 0.
      if (!$token->is(array(T_OPEN_TAG, T_CLOSE_TAG, T_WHITESPACE, 0))) {
        return;
      }
    }

    $this->raiseLintAtPath(
      pht("Empty files usually don't serve any useful purpose."));
  }

}
