<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Scalar\Int_
 */
final class ArcanistHexadecimalNumericScalarCasingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 127;

  public function getLintName() {
    return pht('Hexadecimal Integer Casing');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Scalar\Int_ &&
      $node->getAttribute('kind') === PhpParser\Node\Scalar\Int_::KIND_HEX) {

      $raw_value = $node->getAttribute('rawValue');

      if (!preg_match('/^0x[0-9A-F]+$/', $raw_value)) {
        $this->raiseLintAtNode(
          $node,
          pht(
            'For consistency, write hexadecimals integers '.
            'in uppercase with a leading `%s`.',
            '0x'),
          '0x'.strtoupper(substr($raw_value, 2)),
          $token_stream);
      }
    }
  }

}
