<?php

final class ArcanistUnableToParsePHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 2;

  public function getLintName() {
    return pht('Unable to Parse');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    // This linter rule isn't used explicitly.
  }

}
