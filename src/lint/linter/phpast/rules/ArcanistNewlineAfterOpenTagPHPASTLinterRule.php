<?php

final class ArcanistNewlineAfterOpenTagPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 81;

  public function getLintName() {
    return pht('Newline After PHP Open Tag');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    $open_tags = array();

    foreach ($token_stream as $i => $token) {
      if ($token->is(T_OPEN_TAG)) {
        $open_tags[] = $i;
      }
    }

    foreach ($open_tags as $open_tag_index) {
      $open_tag = $token_stream[$open_tag_index];
      $open_tag_has_newline = $open_tag->text === "<?php\n";

      for ($i = $open_tag_index + 1; $i < count($token_stream); $i++) {
        $next = $token_stream[$i];

        if ($next->is(T_WHITESPACE)) {
          // PHP tokenizes the whitespace following the open tag
          // as part of T_OPEN_TAG, but only up to the first newline.
          if (
            $open_tag_has_newline &&
            strpos($next->text, "\n") !== false) {
            continue 2;
          }

          if (preg_match('/\n\s*\n/', $next->text)) {
            continue 2;
          }
        }

        if ($next->is(T_CLOSE_TAG)) {
          continue 2;
        }

        if ($open_tag->line === $next->line) {
          continue;
        }

        $this->raiseLintAtToken(
          $next,
          pht(
            '`%s` should be separated from code by an empty line.',
            '<?php'),
          "\n".$next->text);
        break;
      }
    }
  }

}
