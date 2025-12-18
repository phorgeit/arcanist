<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassLike
 */
final class ArcanistMethodSpacingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 138;

  public function getLintName() {
    return pht('Method Spacing');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (!($node instanceof PhpParser\Node\Stmt\ClassLike)) {
      return;
    }

    $methods = array();

    foreach ($node->getMethods() as $method) {
      $doc_comment = $method->getDocComment();
      if ($doc_comment) {
        $methods[$doc_comment->getStartTokenPos()] = $method;
      } else {
        $methods[$method->getStartTokenPos()] = $method;
      }
    }

    if (!$methods) {
      return;
    }

    $last_method_end_pos = -1;

    foreach ($methods as $start_token_pos => $method) {
      $indentation = $this->getIndentation($method, $token_stream);

      if (($last_method_end_pos + 1) < ($start_token_pos - 1)) {
        $preceding_token = $token_stream[$start_token_pos - 1];

        if (!$preceding_token->is(T_WHITESPACE)) {
          $start_token = $token_stream[$start_token_pos];

          $this->raiseLintAtToken(
            $start_token,
            pht('Methods should have one preceding blank line.'),
            "\n\n".$indentation.$start_token->text);
        } else if (
          !str_ends_with($preceding_token->text, "\n\n".$indentation)) {

          $this->raiseLintAtToken(
            $preceding_token,
            pht('Methods should have one preceding blank line.'),
            "\n\n".$indentation);
        }
      }

      $last_method_end_pos = $method->getEndTokenPos();

      $following_token = $token_stream[$last_method_end_pos + 1];

      if (!$following_token->is(T_WHITESPACE)) {
        $end_token = $token_stream[$last_method_end_pos];

        $this->raiseLintAtToken(
          $end_token,
          pht('Methods should have one preceding blank line.'),
          $end_token->text."\n\n");
      } else if (strncmp($following_token->text, "\n\n", 2) !== 0) {
        if ($following_token->text === "\n".$indentation) {
          $replace = "\n\n".$indentation;
        } else {
          $replace = "\n\n";
        }

        $this->raiseLintAtToken(
          $following_token,
          pht('Methods should be followed by one blank line.'),
          $replace);
      }
    }
  }

}
