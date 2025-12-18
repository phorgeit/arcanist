<?php

/**
 * @phutil-external-symbol class PhpParser\Node\Stmt\InlineHTML
 */
final class ArcanistPHPCloseTagPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 8;

  public function getLintName() {
    return pht('Use of Close Tag `%s`', '?>');
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    $inline_html = $ast->findNodesOfKind(PhpParser\Node\Stmt\InlineHTML::class);

    if (count($inline_html) > 0) {
      return;
    }

    foreach ($token_stream as $token) {
      if ($token->is(T_CLOSE_TAG)) {
        $this->raiseLintAtToken(
          $token,
          pht(
            'Do not use the PHP closing tag, `%s`.',
            '?>'));
      }
    }
  }

}
