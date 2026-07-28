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
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
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

      $this->lintStrstrUsedForCheck($node, $token_stream);
      $this->lintStrposUsedForStart($node, $token_stream);
    }
  }

  private function lintStrstrUsedForCheck(
    PhpParser\Node\Expr\BinaryOp $node,
    array $token_stream) {

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

    $is_negated = $node instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical;

    $name = $strstr->name->toLowerString();
    if ($name === 'strstr' || $name === 'strchr') {
      // strstr, strpos, and str_contains share the same call order.
      $replacement = $this->getString($strstr, $token_stream);
      $replacement = substr($replacement, '6');

      if (version_compare($this->version, '8.0.0', '>=')) {
        $replacement = 'str_contains'.$replacement;
        if (!$is_negated) {
          $replacement = '!'.$replacement;
        }

        $this->raiseLintAtNode(
          $node,
          pht(
            'Use `%s` for checking if the string contains something.',
            'str_contains'),
          $replacement,
          $token_stream);
      } else {
        $replacement = 'strpos'.$replacement;

        $this->raiseLintAtNode(
          $strstr,
          pht(
            'Use `%s` for checking if the string contains something.',
            'strpos'),
          $replacement,
          $token_stream);
      }
    } else if ($name === 'stristr') {
      // stristr and stripos share the same call order.
      $replacement = $this->getString($strstr, $token_stream);
      $replacement = 'stripos'.substr($replacement, '7');

      $this->raiseLintAtNode(
        $strstr,
        pht(
          'Use `%s` for checking if the string contains something.',
          'stripos'),
        $replacement,
        $token_stream);
    }
  }

  private function lintStrposUsedForStart(
    PhpParser\Node\Expr\BinaryOp $node,
    array $token_stream) {

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

    $is_negated = $node instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical;

    $name = $strpos->name->toLowerString();
    if ($name === 'stripos') {
      $replacement = $this->makeStrcmpReplacement($strpos, $token_stream);

      // This negation is opposite of what's expected due to the return type
      // of strncmp/strncasecmp. 0 means equal, but that evaluates to false.
      if ($replacement && !$is_negated) {
        $replacement = '!'.$replacement;
      }

      $this->raiseLintAtNode(
        $node,
        pht(
          'Use `%s` for checking if the string starts with something.',
          'strncasecmp'),
        $replacement,
        $token_stream);
    } else if ($name === 'strpos') {
      if (version_compare($this->version, '8.0.0', '>=')) {
        // strpos and str_starts_with share the same call order.
        $replacement = $this->getString($strpos, $token_stream);
        $replacement = 'str_starts_with'.substr($replacement, '6');

        if ($is_negated) {
          $replacement = '!'.$replacement;
        }

        $this->raiseLintAtNode(
          $node,
          pht(
            'Use `%s` for checking if the string starts with something.',
            'str_starts_with'),
          $replacement,
          $token_stream);
      } else {
        $replacement = $this->makeStrcmpReplacement($strpos, $token_stream);

        // This negation is opposite of what's expected due to the return type
        // of strncmp/strncasecmp. 0 means equal, but that evaluates to false.
        if ($replacement && !$is_negated) {
          $replacement = '!'.$replacement;
        }

        $this->raiseLintAtNode(
          $node,
          pht(
            'Use `%s` for checking if the string starts with something.',
            'strncmp'),
          $replacement,
          $token_stream);
      }
    }
  }

  /**
   * @param PhpParser\Node\Expr\FuncCall $call
   * @param array $token_stream
   * @return string|null
   */
  private function makeStrcmpReplacement(
    PhpParser\Node\Expr\FuncCall $call,
    array $token_stream) {

    if ($call->isFirstClassCallable()) {
      return null;
    }

    if ($call->name->name === 'stripos') {
      $function_name = 'strncasecmp';
    } else {
      $function_name = 'strncmp';
    }

    $arguments = $call->getArgs();

    if (count($arguments) < 2) {
      return null;
    }

    list($haystack, $needle) = $arguments;

    // We can't determine the length to compare against
    // for things that aren't strings.
    if (!($needle->value instanceof PhpParser\Node\Scalar\String_)) {
      return null;
    }

    $length = strlen($needle->value->value);

    return $function_name.'('.
      $this->getString($haystack, $token_stream).', '.
      $this->getString($needle, $token_stream).', '.
      $length.')';
  }

}
