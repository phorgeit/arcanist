<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\List_
 */
final class ArcanistListStylePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 137;

  private $preferredStyle = 'list';

  public function getLintName() {
    return pht('List Style');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function getLinterConfigurationOptions() {
    return parent::getLinterConfigurationOptions() + array(
      'phpast.list-style' => array(
        'type' => 'optional string ("list", "array")',
        'help' => pht('List style to prefer.'),
      ),
    );
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'phpast.list-style':
        if ($value !== 'list' && $value !== 'array') {
          phlog(
            pht(
              'Invalid value for `%s`: %s.',
              'phpast.list-style',
              $value));
          return;
        }
        $this->preferredStyle = $value;
        return;
      default:
        parent::setLinterConfigurationValue($key, $value);
        return;
    }
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($this->version) {
      $target_version = $this->version;
    } else {
      $target_version = PHP_VERSION;
    }

    if (
      !$node instanceof PhpParser\Node\Expr\List_ ||
      version_compare($target_version, '7.1.0', '<=')) {
      return;
    }

    $kind = $node->getAttribute('kind');

    if ($kind === PhpParser\Node\Expr\List_::KIND_LIST) {
      if ($this->preferredStyle === 'list') {
        return;
      }

      $description = pht('List should use the array syntax.');
      $open = '[';
      $close = ']';

      $token_range = array_slice(
        $token_stream,
        $node->getStartTokenPos() + 2,
        $node->getEndTokenPos() - $node->getStartTokenPos() - 2);
    } else {
      if ($this->preferredStyle === 'array') {
        return;
      }

      $description = pht(
        'List statements should use the `%s` statement syntax.',
        'list');
      $open = 'list(';
      $close = ')';

      $token_range = array_slice(
        $token_stream,
        $node->getStartTokenPos() + 1,
        $node->getEndTokenPos() - $node->getStartTokenPos() - 1);
    }

    $replacement = $open.implode('', ppull($token_range, 'text')).$close;

    $this->raiseLintAtOffset(
      $node->getStartFilePos(),
      $description,
      $this->getString($node, $token_stream),
      $replacement);
  }

}
