<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Name
 */
final class ArcanistDeprecationPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 85;

  private $deprecatedFunctions = array();

  public function getLintName() {
    return pht('Use of Deprecated Function');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function getLinterConfigurationOptions() {
    return parent::getLinterConfigurationOptions() + array(
      'phpast.deprecated.functions' => array(
        'type' => 'optional map<string, string>',
        'help' => pht(
          'Functions which should be considered deprecated.'),
      ),
    );
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'phpast.deprecated.functions':
        $this->deprecatedFunctions = $value;
        return;
    }

    parent::setLinterConfigurationValue($key, $value);
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name) {

      $name = $node->name->toLowerString();

      if (empty($this->deprecatedFunctions[$name])) {
        return;
      }

      $this->raiseLintAtNode($node, $this->deprecatedFunctions[$name]);
    }
  }

}
