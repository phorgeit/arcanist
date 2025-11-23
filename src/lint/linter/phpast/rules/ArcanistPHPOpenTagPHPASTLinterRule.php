<?php

final class ArcanistPHPOpenTagPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 15;

  public function getLintName() {
    return pht('Expected Open Tag');
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    foreach ($token_stream as $token) {
      if ($token->is(T_OPEN_TAG)) {
        break;
      } else if ($token->is(T_OPEN_TAG_WITH_ECHO)) {
        break;
      } else {
        if (!preg_match('/^#!/', $token->text)) {
          $this->raiseLintAtToken(
            $token,
            pht(
              'PHP files should start with `%s`, which may be preceded by '.
              'a `%s` line for scripts.',
              '<?php',
              '#!'));
        }
        break;
      }
    }
  }

}
