<?php

final class ArcanistCommentSpacingPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 34;

  public function getLintName() {
    return pht('Comment Spaces');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  /**
   * @param array<PhpParser\Token> $token_stream
   */
  public function process(PhpParserAst $ast, array $token_stream) {
    foreach ($token_stream as $token) {
      if (!$token->is(T_COMMENT)) {
        continue;
      }

      if ($token->text[0] !== '#') {
        $match = null;

        if (preg_match('@^(/[/*]+)[^/*\s]@', $token->text, $match)) {
          $this->raiseLintAtOffset(
            $token->pos,
            pht('Put space after comment start.'),
            $match[1],
            $match[1].' ');
        }
      }
    }
  }

}
