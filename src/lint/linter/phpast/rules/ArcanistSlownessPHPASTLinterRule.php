<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Identical
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\NotIdentical
 * @phutil-external-symbol class PhpParser\Node\Expr\ConstFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Scalar\Int_
 */
final class ArcanistSlownessPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 36;

  public function getLintName() {
    return pht('Slow Construct');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Expr\BinaryOp\Identical ||
      $node instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {

      $this->lintStrstrUsedForCheck($node);
      $this->lintStrposUsedForStart($node);
    }
  }

  private function lintStrstrUsedForCheck(PhpParser\Node\Expr\BinaryOp $node) {
    if (
      $node->right instanceof PhpParser\Node\Expr\ConstFetch &&
      $node->right->name->toLowerString() === 'false') {
      $strstr = $node->left;
    } else if (
      $node->left instanceof PhpParser\Node\Expr\ConstFetch &&
      $node->left->name->toLowerString() === 'false') {
      $strstr = $node->right;
    } else {
      return;
    }

    if (
      !($strstr instanceof PhpParser\Node\Expr\FuncCall) ||
      !($strstr->name instanceof PhpParser\Node\Name)) {

      return;
    }

    $name = $strstr->name->toLowerString();
    if ($name === 'strstr' || $name === 'strchr') {
      $this->raiseLintAtNode(
        $strstr,
        pht(
          'Use `%s` for checking if the string contains something.',
          'strpos'));
    } else if ($name === 'stristr') {
      $this->raiseLintAtNode(
        $strstr,
        pht(
          'Use `%s` for checking if the string contains something.',
          'stripos'));
    }
  }

  private function lintStrposUsedForStart(PhpParser\Node\Expr\BinaryOp $node) {
    if (
      $node->right instanceof PhpParser\Node\Scalar\Int_ &&
      $node->right->value === 0) {
      $strpos = $node->left;
    } else if (
      $node->left instanceof PhpParser\Node\Scalar\Int_ &&
      $node->left->value === 0) {
      $strpos = $node->right;
    } else {
      return;
    }

    if (
      !($strpos instanceof PhpParser\Node\Expr\FuncCall) ||
      !($strpos->name instanceof PhpParser\Node\Name)) {

      return;
    }

    $name = $strpos->name->toLowerString();
    if ($name === 'strpos') {
      $this->raiseLintAtNode(
        $strpos,
        pht(
          'Use `%s` for checking if the string starts with something.',
          'strncmp'));
    } else if ($name === 'stripos') {
      $this->raiseLintAtNode(
        $strpos,
        pht(
          'Use `%s` for checking if the string starts with something.',
          'strncasecmp'));
    }
  }

}
