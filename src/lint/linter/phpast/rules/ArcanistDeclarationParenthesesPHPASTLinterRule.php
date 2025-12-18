<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr
 * @phutil-external-symbol class PhpParser\Node\Expr\Closure
 * @phutil-external-symbol class PhpParser\Node\Expr\ArrowFunction
 * @phutil-external-symbol class PhpParser\Node\PropertyHook
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 */
final class ArcanistDeclarationParenthesesPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 38;

  public function getLintName() {
    return pht('Declaration Formatting');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      $node instanceof PhpParser\Node\Stmt\Function_ ||
      $node instanceof PhpParser\Node\Stmt\ClassMethod) {

      $trailing = $this->getNonsemanticTokensAfterNode(
        $node->name,
        $token_stream);
      $trailing_text = implode('', ppull($trailing, 'text'));

      if (preg_match('/^\s+$/', $trailing_text)) {
        $this->raiseLintAtOffset(
          head($trailing)->pos,
          pht(
            'Convention: no spaces before opening parenthesis in '.
            'function and method declarations.'),
          $trailing_text,
          '');
      }

      if ($node->stmts) {
        $end = head($node->stmts)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }
    } else if (
      $node instanceof PhpParser\Node\Expr\Closure ||
      $node instanceof PhpParser\Node\Expr\ArrowFunction) {

      $leading_text = '';
      $record = false;
      $offset = -1;

      for ($i = $node->getStartTokenPos(); $i < $node->getEndTokenPos(); $i++) {
        $token = $token_stream[$i];
        if ($token->is(array(T_FUNCTION, T_FN))) {
          $record = true;
          $offset = $token_stream[$i + 1]->pos;
        } else if ($token->is('(')) {
          break;
        } else if ($record) {
          $leading_text .= $token->text;
        }
      }

      if ($leading_text !== ' ') {
        $this->raiseLintAtOffset(
          $offset,
          pht(
            'Convention: space before opening parenthesis in '.
            'anonymous function declarations.'),
          $leading_text,
          ' ');
      }

      if ($node instanceof PhpParser\Node\Expr\Closure && $node->uses) {
        $start = head($node->uses)->getStartTokenPos();
        $trailing_offset = -1;
        $trailing_text = '';
        $leading_offset = -1;
        $leading_text = '';
        $record = false;
        $leading = false;

        for ($i = $start; $i > $node->getStartTokenPos(); $i--) {
          $token = $token_stream[$i];
          if ($token->is('(')) {
            $record = true;
            $leading = false;
            $trailing_offset = $token->pos;
            continue;
          }

          if ($token->is(T_USE)) {
            $leading = true;
            $leading_offset = $token->pos;
            continue;
          }

          if ($token->is(')')) {
            break;
          }

          if ($record) {
            if ($leading) {
              $leading_text .= $token->text;
            } else {
              $trailing_text .= $token->text;
            }
          }
        }

        if ($leading_text !== ' ') {
          $this->raiseLintAtOffset(
            $leading_offset - strlen($leading_text),
            pht(
              'Convention: space before `%s` token.',
              'use'),
            $leading_text,
            ' ');
        }

        if ($trailing_text !== ' ') {
          $this->raiseLintAtOffset(
            $trailing_offset - strlen($trailing_text),
            pht(
              'Convention: space after `%s` token.',
              'use'),
            $trailing_text,
            ' ');
        }
      }

      if ($node instanceof PhpParser\Node\Expr\ArrowFunction) {
        $end = $node->expr->getStartTokenPos();
      } else if ($node->uses) {
        $end = head($node->uses)->getStartTokenPos();
      } else if ($node->stmts) {
        $end = head($node->stmts)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }
    } else if (
      $node instanceof PhpParser\Node\PropertyHook &&
      $node->name->toLowerString() === 'set') {

      $trailing = $this->getNonsemanticTokensAfterNode(
        $node->name,
        $token_stream);
      $trailing_text = implode('', ppull($trailing, 'text'));

      if (preg_match('/^\s+$/', $trailing_text)) {
        $this->raiseLintAtOffset(
          head($trailing)->pos,
          pht(
            'Convention: no spaces before opening parenthesis in '.
            'property hooks.'),
          $trailing_text,
          '');
      }

      $start = $node->name->getEndTokenPos();

      if ($node->body instanceof PhpParser\Node\Expr) {
        $end = $node->body->getStartTokenPos();
      } else if ($node->body && is_array($node->body)) {
        $end = head($node->body)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }

      // The parameter list may be omitted entirely.
      $has_parentheses = false;
      for ($i = $start; $i < $end; $i++) {
        if ($token_stream[$i]->is('(')) {
          $has_parentheses = true;
          break;
        }
      }

      if (!$has_parentheses) {
        return;
      }
    } else {
      return;
    }

    $trailing_text = '';
    $record = false;
    $offset = -1;

    for ($i = $end - 1; $i > $node->getStartTokenPos(); $i--) {
      $token = $token_stream[$i];

      if ($token->is(')')) {
        $record = true;
        $offset = $token->pos;
        continue;
      }

      if ($record) {
        if ($token->isIgnorable()) {
          $trailing_text .= $token->text;
        } else {
          break;
        }
      }
    }

    if (preg_match('/^\s+$/', $trailing_text)) {
      $this->raiseLintAtOffset(
        $offset - strlen($trailing_text),
        pht(
          'Convention: no spaces before closing parenthesis in '.
          'function and method declarations.'),
        $trailing_text,
        '');
    }
  }

}
