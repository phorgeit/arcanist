<?php

final class ArcanistTodoCommentPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 16;

  public function getLintName() {
    return pht('TODO Comment');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_DISABLED;
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    foreach ($token_stream as $token) {
      if ($token->is(array(T_COMMENT, T_DOC_COMMENT))) {
        if ($token->is(T_DOC_COMMENT)) {
          $regex = '/(TODO|@todo)/';
        } else {
          $regex = '/TODO/';
        }

        $matches = null;
        $preg = preg_match_all(
          $regex,
          $token->text,
          $matches,
          PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $match) {
          list($string, $offset) = $match;
          $this->raiseLintAtOffset(
            $token->pos + $offset,
            pht('This comment has a TODO.'),
            $string);
        }
      }
    }
  }

}
