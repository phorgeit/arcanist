<?php

final class ArcanistSyntaxErrorPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 1;

  public function getLintName() {
    return pht('PHP Syntax Error!');
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    // This linter rule isn't used explicitly.
  }

}
