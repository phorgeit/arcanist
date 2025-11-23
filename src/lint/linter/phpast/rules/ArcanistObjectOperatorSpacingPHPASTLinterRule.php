<?php

final class ArcanistObjectOperatorSpacingPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 74;

  public function getLintName() {
    return pht('Object Operator Spacing');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    foreach ($token_stream as $i => $token) {
      if ($token->is(T_OBJECT_OPERATOR)) {
        $before = $this->getNonsemanticTokensBefore($i, $token_stream);
        $after = $this->getNonsemanticTokensAfter($i, $token_stream);

        if ($before) {
          $value = implode('', ppull($before, 'text'));

          if (strpos($value, "\n") !== false) {
            continue;
          }

          $this->raiseLintAtOffset(
            head($before)->pos,
            pht('There should be no whitespace before the object operator.'),
            $value,
            '');
        }

        if ($after) {
          $this->raiseLintAtOffset(
            last($after)->pos,
            pht('There should be no whitespace after the object operator.'),
            implode('', ppull($before, 'text')),
            '');
        }
      }
    }
  }

}
