<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Expr\New_
 * @phutil-external-symbol class PhpParser\Node\Name
 */
final class ArcanistUnsafeDynamicStringPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 86;

  private $dynamicStringFunctions = array();
  private $dynamicStringClasses   = array();

  public function getLintName() {
    return pht('Unsafe Usage of Dynamic String');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ERROR;
  }

  public function getLinterConfigurationOptions() {
    $options = array(
      'phpast.dynamic-string.classes' => array(
        'type' => 'optional map<string, string>',
        'help' => pht(
          'Classes which should not be used because they represent the '.
          'unsafe usage of dynamic strings.'),
      ),
      'phpast.dynamic-string.functions' => array(
        'type' => 'optional map<string, string>',
        'help' => pht(
          'Functions which should not be used because they represent the '.
          'unsafe usage of dynamic strings.'),
      ),
    );

    return $options + parent::getLinterConfigurationOptions();
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'phpast.dynamic-string.classes':
        $this->dynamicStringClasses = $value;
        return;

      case 'phpast.dynamic-string.functions':
        $this->dynamicStringFunctions = $value;
        return;

      default:
        parent::setLinterConfigurationValue($key, $value);
        return;
    }
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\New_) {
      $safe = array_change_key_case($this->dynamicStringClasses);
      $name = $node->class;
    } else if ($node instanceof PhpParser\Node\Expr\FuncCall) {
      $safe = array_change_key_case($this->dynamicStringFunctions);
      $name = $node->name;
    } else {
      return;
    }

    if (!($name instanceof PhpParser\Node\Name)) {
      return;
    }

    $param = idx($safe, $name->toLowerString());

    if ($param === null || count($node->args) <= $param) {
      return;
    }

    $identifier = $node->args[$param]->value;
    if (!$this->isConstantString($identifier)) {
      $this->raiseLintAtNode(
        $node,
        pht(
          "Parameter %d of `%s` should be a scalar string, ".
          "otherwise it's not safe.",
          $param + 1,
          $name->toString()));
    }
  }

}
