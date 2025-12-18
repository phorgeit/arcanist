<?php

abstract class ArcanistPHPASTTreeLinterRule extends ArcanistPHPASTLinterRule {

  /**
   * @param PhpParserAst $ast
   * @param array<PhpParser\Token> $token_stream
   */
  abstract public function process(PhpParserAst $ast, array $token_stream);

}
