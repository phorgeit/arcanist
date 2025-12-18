<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\Array_
 */
final class ArcanistArrayValuePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 76;

  public function getLintName() {
    return pht('Array Element');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\Array_) {
      $multiline = $node->getStartLine() !== $node->getEndLine();

      // There is no need to check an empty array.
      if (!$node->items || !$multiline) {
        return;
      }

      foreach ($node->items as $item) {
        $before = $this->getNonsemanticTokensBeforeNode($item, $token_stream);
        if (strpos(implode('', ppull($before, 'text')), "\n") === false) {
          if (last($before) && last($before)->is(T_WHITESPACE)) {
            $token = last($before);
            $replacement = "\n".$this->getIndentation($item, $token_stream);
          } else {
            $token = $token_stream[$item->getStartTokenPos()];
            $replacement = "\n".
              $this->getIndentation($item, $token_stream).
              $token->text;
          }

          $this->raiseLintAtToken(
            $token,
            pht('Array elements should each occupy a single line.'),
            $replacement);
        }
      }
    }
  }

}
