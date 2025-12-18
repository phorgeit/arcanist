<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\CallLike
 */
final class ArcanistArgumentAlignmentPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 140;

  public function getLintName() {
    return pht('Argument Alignment');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      !($node instanceof PhpParser\Node\Expr\CallLike) ||
      $node->isFirstClassCallable()) {
      return;
    }

    $arguments = $node->getArgs();

    if (count($arguments) < 2) {
      return;
    }

    $first = head($arguments);
    $last = last($arguments);

    if (
      $first->getStartLine() === $last->getStartLine() &&
      $node->getStartLine() === $first->getStartLine()) {
      return;
    }

    $indentation = $this->getIndentation($node, $token_stream).'  ';

    $last_argument_line = $node->getStartLine();
    foreach ($arguments as $argument) {
      if ($last_argument_line === $argument->getStartLine()) {
        $before = $this->getNonsemanticTokensBeforeNode(
          $argument,
          $token_stream);

        if ($before) {
          $offset = head($before)->pos;
          $original = implode('', ppull($before, 'text'));
        } else {
          $offset = $argument->getStartFilePos();
          $original = '';
        }

        $concrete_argument = $this->getString($argument, $token_stream);
        $original .= $concrete_argument;

        $this->raiseLintAtOffset(
          $offset,
          pht(
            'In multi-line function or method calls, '.
            'each argument should be on a separate line.'),
          $original,
          "\n".$indentation.$concrete_argument);
      }
      $last_argument_line = $argument->getStartLine();
    }
  }

}
