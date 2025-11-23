<?php

/**
 * @phutil-external-symbol class PhpParser\ConstExprEvaluator
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Name
 */
final class ArcanistProductNameLiteralPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 134;

  public function getLintName() {
    return pht('Use of Product Name Literal');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    static $search_pattern;

    if (!$search_pattern) {
      $product_names = PlatformSymbols::getProductNames();
      foreach ($product_names as $k => $product_name) {
        $product_names[$k] = preg_quote($product_name);
      }

      $search_pattern = '(\b(?:'.implode('|', $product_names).')\b)i';
    }

    if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name &&
      $node->name->toLowerString() === 'pht' &&
      $node->args) {

      $identifier = head($node->args)->value;

      if (!$this->isConstantString($identifier)) {
        return;
      }

      $literal_value = id(new PhpParser\ConstExprEvaluator())
        ->evaluateSilently($identifier);

      $matches = phutil_preg_match_all($search_pattern, $literal_value);
      if (!$matches[0]) {
        return;
      }

      $name_list = array();
      foreach ($matches[0] as $match) {
        $name_list[phutil_utf8_strtolower($match)] = $match;
      }
      $name_list = implode(', ', $name_list);

      $this->raiseLintAtNode(
        $node->args[0],
        pht(
          'Avoid use of product name literals in "pht()": use generic '.
          'language or an appropriate method from the "PlatformSymbols" class '.
          'instead so the software can be forked. String uses names: %s.',
          $name_list));
    }
  }

}
