<?php

final class ArcanistPHPEchoTagPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 7;

  public function getLintName() {
    return pht('Use of Echo Tag `%s`', '<?=');
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    foreach ($token_stream as $token) {
      if ($token->is(T_OPEN_TAG_WITH_ECHO)) {
        $this->raiseLintAtToken(
          $token,
          pht(
            'Avoid the PHP echo short form, `%s`.',
            '<?='));
      }
    }
  }

}
