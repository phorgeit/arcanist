<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\ArrowFunction
 * @phutil-external-symbol class PhpParser\Node\Expr\Closure
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 */
final class ArcanistParameterAlignmentPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 139;

  public function getLintName() {
    return pht('Parameter Alignment');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\Closure) {
      $has_multiline_parameters = $this->checkAlignment($node, $token_stream);
      $has_multiline_uses = $this->checkUse($node, $token_stream);

      $is_multiline = $has_multiline_parameters || $has_multiline_uses;
    } else if ($node instanceof PhpParser\Node\FunctionLike) {
      $is_multiline = $this->checkAlignment($node, $token_stream);
    } else {
      return;
    }

    if (
      $is_multiline &&
      $node->getStmts() &&
      !($node instanceof PhpParser\Node\Expr\ArrowFunction)) {

      $indentation = $this->getIndentation($node, $token_stream).'  ';
      $first_statement = head($node->getStmts());
      $before = $this->getNonsemanticTokensBeforeNode(
        $first_statement,
        $token_stream);

      if (!$before) {
        $replacement = "\n\n".
          $indentation.
          $this->getString($first_statement, $token_stream);

        $this->raiseLintAtNode(
          $first_statement,
          pht(
            'Multi-line parameter declarations should be followed by a '.
            'blank line.'),
          $replacement,
          $token_stream);
      } else {
        $first_non_semantic_token = head($before);
        $expected_indentation = "\n\n".$indentation;

        if (!$first_non_semantic_token->is(T_WHITESPACE)) {
          $this->raiseLintAtToken(
            $first_non_semantic_token,
            pht(
              'Multi-line parameter declarations should be followed by a '.
              'blank line.'),
            $expected_indentation.$first_non_semantic_token->text);
        } else if ($first_non_semantic_token->text !== $expected_indentation) {
          $this->raiseLintAtToken(
            $first_non_semantic_token,
            pht(
              'Multi-line parameter declarations should be followed by a '.
              'blank line.'),
            $expected_indentation);
        }
      }
    }
  }

  private function checkAlignment(
    PhpParser\Node\FunctionLike $function_like,
    array $token_stream): bool {

    $parameters = $function_like->getParams();

    if (count($parameters) < 2) {
      if ($parameters) {
        $first = head($parameters);
        return $function_like->getStartLine() !== $first->getStartLine();
      }

      return false;
    }

    $first = head($parameters);
    $last = last($parameters);

    if (
      $first->getStartLine() === $last->getStartLine() &&
      $function_like->getStartLine() === $first->getStartLine()) {
      return false;
    }

    $indentation = $this->getIndentation($function_like, $token_stream).'  ';

    $last_parameter_line = $function_like->getStartLine();
    foreach ($parameters as $parameter) {
      if ($last_parameter_line === $parameter->getStartLine()) {
        $before = $this->getNonsemanticTokensBeforeNode(
          $parameter,
          $token_stream);

        if ($before) {
          $offset = head($before)->pos;
          $original = implode('', ppull($before, 'text'));
        } else {
          $offset = $parameter->getStartFilePos();
          $original = '';
        }

        $concrete_parameter = $this->getString($parameter, $token_stream);
        $original .= $concrete_parameter;

        $this->raiseLintAtOffset(
          $offset,
          pht(
            'In multi-line parameter declarations, '.
            'each parameter should be on a separate line.'),
          $original,
          "\n".$indentation.$concrete_parameter);
      }
      $last_parameter_line = $parameter->getStartLine();
    }

    return true;
  }

  private function checkUse(
    PhpParser\Node\Expr\Closure $closure,
    array $token_stream): bool {

    if (count($closure->uses) < 2) {
      if ($closure->uses) {
        return $closure->getStartLine() !== $closure->uses[0]->getStartLine();
      }

      return false;
    }

    $first = head($closure->uses);
    $last = last($closure->uses);

    if ($closure->params) {
      $last_parameter_line = last($closure->params)->getStartLine();
    } else {
      $last_parameter_line = $closure->getStartLine();
    }

    if (
      $first->getStartLine() === $last->getStartLine() &&
      $last_parameter_line === $first->getStartLine()) {
      return false;
    }


    $indentation = $this->getIndentation($closure, $token_stream).'  ';

    $last_use_line = $last_parameter_line;
    foreach ($closure->uses as $use) {
      if ($last_use_line === $use->getStartLine()) {
        $before = $this->getNonsemanticTokensBeforeNode(
          $use,
          $token_stream);

        if ($before) {
          $offset = head($before)->pos;
          $original = implode('', ppull($before, 'text'));
        } else {
          $offset = $use->getStartFilePos();
          $original = '';
        }

        $concrete_use = $this->getString($use, $token_stream);
        $original .= $concrete_use;

        $this->raiseLintAtOffset(
          $offset,
          pht(
            'In multi-line use declarations, '.
            'each variable should be on a separate line.'),
          $original,
          "\n".$indentation.$concrete_use);
      }
      $last_use_line = $use->getStartLine();
    }

    return true;
  }

}
