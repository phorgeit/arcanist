<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Stmt\For_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Foreach_
 * @phutil-external-symbol class PhpParser\Node\Stmt\While_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ElseIf_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Switch_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Catch_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Declare_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Do_
 * @phutil-external-symbol class PhpParser\Node\Stmt\If_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Unset_
 * @phutil-external-symbol class PhpParser\Node\Expr\Isset_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Unset_
 * @phutil-external-symbol class PhpParser\Node\Expr\Isset_
 * @phutil-external-symbol class PhpParser\Node\Expr\Exit_
 * @phutil-external-symbol class PhpParser\Node\Expr\Empty_
 * @phutil-external-symbol class PhpParser\Node\Expr\List_
 * @phutil-external-symbol class PhpParser\Node\Expr\Array_
 * @phutil-external-symbol class PhpParser\Node\Expr\Closure
 * @phutil-external-symbol class PhpParser\Node\Expr\ArrowFunction
 * @phutil-external-symbol class PhpParser\Node\Expr\Match_
 * @phutil-external-symbol class PhpParser\Node\Expr\New_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 * @phutil-external-symbol class PhpParser\Node\Expr\MethodCall
 * @phutil-external-symbol class PhpParser\Node\Expr\NullsafeMethodCall
 * @phutil-external-symbol class PhpParser\Node\Expr\StaticCall
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\PropertyHook
 * @phutil-external-symbol class PhpParser\Node\Expr
 */
final class ArcanistParenthesesSpacingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 25;

  public function getLintName() {
    return pht('Spaces Inside Parentheses');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    $short_syntax = false;
    $kind = $node->getAttribute('kind');
    $content_start = null;
    $content_end = null;

    if (
      $node instanceof PhpParser\Node\Stmt\Function_ ||
      $node instanceof PhpParser\Node\Stmt\ClassMethod) {
      $start = $node->getStartTokenPos();

      if ($node->returnType) {
        $end = $node->returnType->getStartTokenPos();
      } else if ($node->stmts) {
        $end = head($node->stmts)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }

      if ($node->params) {
        $content_start = head($node->params)->getStartTokenPos();
        $content_end = last($node->params)->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Stmt\For_) {
      $start = $node->getStartTokenPos();

      if ($node->stmts) {
        $end = head($node->stmts)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }

      if ($node->init) {
        $content_start = head($node->init)->getStartTokenPos();
      } else if ($node->cond) {
        $content_start = head($node->cond)->getStartTokenPos();
      } else if ($node->loop) {
        $content_start = head($node->loop)->getStartTokenPos();
      }

      if ($node->loop) {
        $content_end = last($node->loop)->getEndTokenPos();
      } else if ($node->cond) {
        $content_end = last($node->cond)->getEndTokenPos();
      } else if ($node->init) {
        $content_end = last($node->init)->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Stmt\Foreach_) {
      $start = $node->getStartTokenPos();

      if ($node->stmts) {
        $end = head($node->stmts)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }

      $content_start = $node->expr->getStartTokenPos();
      $content_end = $node->valueVar->getEndTokenPos();
    } else if (
      $node instanceof PhpParser\Node\Stmt\While_ ||
      $node instanceof PhpParser\Node\Stmt\ElseIf_) {
      $start = $node->getStartTokenPos();

      if ($node->stmts) {
        $end = head($node->stmts)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }

      $content_start = $node->cond->getStartTokenPos();
      $content_end = $node->cond->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\Switch_) {
      $start = $node->getStartTokenPos();

      if ($node->cases) {
        $end = head($node->cases)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }

      $content_start = $node->cond->getStartTokenPos();
      $content_end = $node->cond->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\Catch_) {
      $start = $node->getStartTokenPos();

      if ($node->stmts) {
        $end = head($node->stmts)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }

      $content_start = head($node->types)->getStartTokenPos();
      $content_end = last($node->types)->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\Declare_) {
      $start = $node->getStartTokenPos();

      if ($node->stmts) {
        $end = head($node->stmts)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }

      $content_start = head($node->declares)->getStartTokenPos();
      $content_end = last($node->declares)->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\Do_) {
      if ($node->stmts) {
        $start = last($node->stmts)->getEndTokenPos();
      } else {
        $start = $node->getStartTokenPos();
      }

      $end = $node->getEndTokenPos();

      $content_start = $node->cond->getStartTokenPos();
      $content_end = $node->cond->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\If_) {
      $start = $node->getStartTokenPos();

      if ($node->stmts) {
        $end = head($node->stmts)->getStartTokenPos();
      } else if ($node->elseifs) {
        $end = head($node->elseifs)->getStartTokenPos();
      } else if ($node->else) {
        $end = $node->else->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }

      $content_start = $node->cond->getStartTokenPos();
      $content_end = $node->cond->getEndTokenPos();
    } else if (
      $node instanceof PhpParser\Node\Stmt\Unset_ ||
      $node instanceof PhpParser\Node\Expr\Isset_) {

      $start = $node->getStartTokenPos();
      $end = $node->getEndTokenPos();

      $content_start = head($node->vars)->getStartTokenPos();
      $content_end = last($node->vars)->getEndTokenPos();
    } else if (
      $node instanceof PhpParser\Node\Expr\Exit_ ||
      $node instanceof PhpParser\Node\Expr\Empty_) {

      if (!$node->expr) {
        return;
      }

      $start = $node->getStartTokenPos();
      $end = $node->getEndTokenPos();

      $content_start = $node->expr->getStartTokenPos();
      $content_end = $node->expr->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Expr\List_) {
      if ($kind === PhpParser\Node\Expr\List_::KIND_ARRAY) {
        $short_syntax = true;
      }

      $start = $node->getStartTokenPos();
      $end = $node->getEndTokenPos();
      // Filter out empty entries (e.g. list(,$var,))
      $items = array_filter($node->items);

      if ($items) {
        $content_start = head($items)->getStartTokenPos();
        $content_end = last($items)->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Expr\Array_) {
      if ($kind === PhpParser\Node\Expr\Array_::KIND_SHORT) {
        $short_syntax = true;
      }

      $start = $node->getStartTokenPos();
      $end = $node->getEndTokenPos();

      if ($node->items) {
        $content_start = head($node->items)->getStartTokenPos();
        $content_end = last($node->items)->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Expr\Closure) {
      $start = $node->getStartTokenPos();

      if ($node->params) {
        $content_start = head($node->params)->getStartTokenPos();
        $content_end = last($node->params)->getEndTokenPos();
      }

      if ($node->uses) {
        $end = head($node->uses)->getStartTokenPos();

        if ($node->params) {
          $use_start = $content_start;
        } else {
          $use_start = -1;
          for ($i = $start; $i < $end; $i++) {
            if ($token_stream[$i]->is(T_USE)) {
              $use_start = $i;
              break;
            }
          }
        }

        if ($node->returnType) {
          $use_end = $node->returnType->getStartTokenPos();
        } else if ($node->stmts) {
          $use_end = head($node->stmts)->getStartTokenPos();
        } else {
          $use_end = $node->getEndTokenPos();
        }

        $this->lintSpacing(
          $use_start,
          $use_end,
          null,
          null,
          false,
          $token_stream);
      } else if ($node->returnType) {
        $end = $node->returnType->getStartTokenPos();
      } else if ($node->stmts) {
        $end = head($node->stmts)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Expr\ArrowFunction) {
      $start = $node->getStartTokenPos();

      if ($node->returnType) {
        $end = $node->returnType->getStartTokenPos();
      } else {
        $end = $node->expr->getStartTokenPos();
      }

      if ($node->params) {
        $content_start = head($node->params)->getStartTokenPos();
        $content_end = last($node->params)->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Expr\Match_) {
      $start = $node->getStartTokenPos();
      $end = head($node->arms)->getStartTokenPos();

      $content_start = $node->cond->getStartTokenPos();
      $content_end = $node->cond->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Expr\New_) {
      $start = $node->getStartTokenPos();

      if (
        ($node->class instanceof PhpParser\Node\Stmt\Class_) &&
        $node->class->stmts) {
        $end = head($node->class->stmts)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }
    } else if (
      $node instanceof PhpParser\Node\Expr\MethodCall ||
      $node instanceof PhpParser\Node\Expr\NullsafeMethodCall ||
      $node instanceof PhpParser\Node\Expr\StaticCall ||
      $node instanceof PhpParser\Node\Expr\FuncCall) {

      $start = $node->name->getEndTokenPos();
      $end = $node->getEndTokenPos();

      if ($node->args) {
        $content_start = head($node->args)->getStartTokenPos();
        $content_end = last($node->args)->getEndTokenPos();
      }
    } else if (
      $node instanceof PhpParser\Node\PropertyHook &&
      $node->name->toLowerString() === 'set') {

      $start = $node->name->getEndTokenPos();

      if ($node->body instanceof PhpParser\Node\Expr) {
        $end = $node->body->getStartTokenPos();
      } else if ($node->body && is_array($node->body)) {
        $end = head($node->body)->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }

      if ($node->params) {
        $content_start = head($node->params)->getStartTokenPos();
        $content_end = last($node->params)->getEndTokenPos();
      } else {
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
      }
    } else {
      return;
    }

    $this->lintSpacing(
      $start,
      $end,
      $content_start,
      $content_end,
      $short_syntax,
      $token_stream);
  }

  private function lintSpacing(
    int $start,
    int $end,
    $contents_start,
    $contents_end,
    bool $short_syntax,
    array $token_stream) {

    if (
      $start < 0 || $start >= count($token_stream) ||
      $end < 0 || $end >= count($token_stream)) {
      return;
    }

    $parenthesis_open = -1;
    for ($i = $start; $i < ($contents_start ?? $end); $i++) {
      $token = $token_stream[$i];

      if ($short_syntax) {
        if ($token->is('[')) {
          $parenthesis_open = $i;
          break;
        }
      } else {
        if ($token->is('(')) {
          $parenthesis_open = $i;
          break;
        }
      }
    }

    $parenthesis_close = -1;
    for ($i = $end; $i > ($contents_end ?? $start); $i--) {
      $token = $token_stream[$i];

      if ($short_syntax) {
        if ($token->is(']')) {
          $parenthesis_close = $i;
          break;
        }
      } else {
        if ($token->is(')')) {
          $parenthesis_close = $i;
          break;
        }
      }
    }

    if ($parenthesis_open === -1 || $parenthesis_close === -1) {
      return;
    }

    $leading_tokens = $this->getNonsemanticTokensAfter(
      $parenthesis_open,
      $token_stream);
    $trailing_tokens = $this->getNonsemanticTokensBefore(
      $parenthesis_close,
      $token_stream);

    $leading_text = implode('', ppull($leading_tokens, 'text'));
    $trailing_text = implode('', ppull($trailing_tokens, 'text'));

    $raise = array();
    if (preg_match('/^ +$/', $leading_text)) {
      $raise[] = array(
        $token_stream[$parenthesis_open + 1]->pos,
        $leading_text,
      );
    }

    if (
      $leading_tokens !== $trailing_tokens
      && preg_match('/^ +$/', $trailing_text)) {

      $raise[] = array(
        $token_stream[$parenthesis_close]->pos - strlen($trailing_text),
        $trailing_text,
      );
    }

    foreach ($raise as $warning) {
      list($offset, $string) = $warning;
      $this->raiseLintAtOffset(
        $offset,
        pht('Parentheses should hug their contents.'),
        $string,
        '');
    }
  }

}
