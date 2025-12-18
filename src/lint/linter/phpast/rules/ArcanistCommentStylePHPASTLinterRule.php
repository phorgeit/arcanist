<?php

final class ArcanistCommentStylePHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 18;

  public function getLintName() {
    return pht('Comment Style');
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
        continue;
      }

      // Don't warn about PHP comment directives. In particular, we need
      // to use "#[\ReturnTypeWillChange]" to implement "Iterator" in a way
      // that is compatible with PHP 8.1 and older versions of PHP prior
      // to the introduction of return types. See T13588.
      if (preg_match('/^#\\[\\\\/', $token->text)) {
        continue;
      }

      $this->raiseLintAtOffset(
        $token->pos,
        pht(
          'Use `%s` single-line comments, not `%s`.',
          '//',
          '#'),
        '#',
        preg_match('/^#\S/', $token->text) ? '// ' : '//');
    }
  }

}
