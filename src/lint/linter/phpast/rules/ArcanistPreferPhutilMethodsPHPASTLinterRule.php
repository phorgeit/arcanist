<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Expr\Exit_
 * @phutil-external-symbol class PhpParser\Node\Expr\Eval_
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 */
final class ArcanistPreferPhutilMethodsPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 141;

  private $prefers = array(
    'preg_replace_callback' => 'phutil_preg_replace_callback',
    'preg_match_all' => 'phutil_preg_match_all',
    'preg_match' => 'phutil_preg_match',
  );

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name) {

      $name = $node->name->toLowerString();
    } else {
      return;
    }

    $prefer = idx($this->prefers, $name);
    if ($prefer) {
      $reason = $this->explain($name, $prefer);
      $this->raiseLintAtNode($node, $reason);
    }
  }

  private function explain($bad_method, $better_method) {
    return pht(
      'Prefer `%s` to `%s`; It provides better error handling.',
      $better_method,
      $bad_method);
  }

  public function getLintName() {
    return pht('Prefer phutil_* methods');
  }

  public function getLintSeverity() {
    // This rule only applies to the Phorge project itself
    return ArcanistLintSeverity::SEVERITY_DISABLED;
  }

}
