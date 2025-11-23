<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Expr\Exit_
 * @phutil-external-symbol class PhpParser\Node\Expr\Eval_
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 */
final class ArcanistBlacklistedFunctionPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 51;

  private $blacklistedFunctions = array();

  public function getLintName() {
    return pht('Use of Blacklisted Function');
  }

  public function getLinterConfigurationOptions() {
    return parent::getLinterConfigurationOptions() + array(
      'phpast.blacklisted.function' => array(
        'type' => 'optional map<string, string>',
        'help' => pht('Blacklisted functions which should not be used.'),
      ),
    );
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'phpast.blacklisted.function':
        $this->blacklistedFunctions = $value;
        return;
    }

    parent::setLinterConfigurationValue($key, $value);
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\Eval_) {
      $name = 'eval';
    } else if ($node instanceof PhpParser\Node\Expr\Exit_) {
      if ($node->getAttribute('kind') === PhpParser\Node\Expr\Exit_::KIND_DIE) {
        $name = 'die';
      } else {
        $name = 'exit';
      }
    } else if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name) {

      $name = $node->name->toLowerString();
    } else {
      return;
    }

    $reason = idx($this->blacklistedFunctions, $name);

    if ($reason) {
      $this->raiseLintAtNode($node, $reason);
    }
  }

}
