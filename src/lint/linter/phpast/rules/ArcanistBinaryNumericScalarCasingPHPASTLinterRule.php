<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Scalar\Int_
 */
final class ArcanistBinaryNumericScalarCasingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 131;

  public function getLintName() {
    return pht('Binary Integer Casing');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Scalar\Int_ &&
      $node->getAttribute('kind') === PhpParser\Node\Scalar\Int_::KIND_BIN) {

      $raw_value = $node->getAttribute('rawValue');

      if (!preg_match('/^0b[01]+$/', $raw_value)) {
        $this->raiseLintAtNode(
          $node,
          pht(
            'For consistency, write binary integers with a leading `%s`.',
            '0b'),
          '0b'.substr($raw_value, 2),
          $token_stream);
      }
    }
  }

}
