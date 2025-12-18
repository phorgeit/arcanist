<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\Array_
 */
final class ArcanistArrayStylePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 136;

  private $preferredStyle = 'long';

  public function getLintName() {
    return pht('Array Style');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function getLinterConfigurationOptions() {
    return parent::getLinterConfigurationOptions() + array(
      'phpast.array-style' => array(
        'type' => 'optional string ("long", "short")',
        'help' => pht('Array style to prefer.'),
      ),
    );
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'phpast.array-style':
        if ($value !== 'long' && $value !== 'short') {
          phlog(
            pht(
              'Invalid value for `%s`: %s.',
              'phpast.array-style',
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
      !$node instanceof PhpParser\Node\Expr\Array_ ||
      version_compare($target_version, '5.3.0', '<=')) {
      return;
    }

    $kind = $node->getAttribute('kind');

    if ($kind === PhpParser\Node\Expr\Array_::KIND_LONG) {
      if ($this->preferredStyle === 'long') {
        return;
      }

      $description = pht('Arrays should use the short array syntax.');
      $open = '[';
      $close = ']';

      $token_range = array_slice(
        $token_stream,
        $node->getStartTokenPos() + 2,
        $node->getEndTokenPos() - $node->getStartTokenPos() - 2);
    } else {
      if ($this->preferredStyle === 'short') {
        return;
      }

      $description = pht('Arrays should use the long array syntax.');
      $open = 'array(';
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
