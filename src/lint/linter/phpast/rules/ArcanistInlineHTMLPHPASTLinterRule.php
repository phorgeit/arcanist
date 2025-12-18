<?php

/**
 * @phutil-external-symbol class PhpParser\Node\Stmt\InlineHTML
 */
final class ArcanistInlineHTMLPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 78;

  public function getLintName() {
    return pht('Inline HTML');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_DISABLED;
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    $inline_htmls = $ast->findNodesOfKind(
      PhpParser\Node\Stmt\InlineHTML::class);

    foreach ($inline_htmls as $inline_html) {
      $offset = $inline_html->getStartFilePos();
      $lines = phutil_split_lines($inline_html->value);

      foreach ($lines as $line) {
        if (strncmp($line, '#!', 2) === 0) {
          $offset += strlen($line);
          // Ignore shebang lines.
          continue;
        }

        if (preg_match('/^\s*$/', $line)) {
          $offset += strlen($line);
          continue;
        }

        $this->raiseLintAtOffset(
          $offset,
          pht('PHP files must only contain PHP code.'));
        break 2;
      }
    }
  }

}
