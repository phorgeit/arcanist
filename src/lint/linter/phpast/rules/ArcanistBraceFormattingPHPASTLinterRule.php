<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\Closure
 * @phutil-external-symbol class PhpParser\Node\Expr\Match_
 * @phutil-external-symbol class PhpParser\Node\PropertyHook
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Stmt\Declare_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Do_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Enum_
 * @phutil-external-symbol class PhpParser\Node\Stmt\For_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Foreach_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 * @phutil-external-symbol class PhpParser\Node\Stmt\If_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Interface_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Property
 * @phutil-external-symbol class PhpParser\Node\Stmt\Switch_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Trait_
 * @phutil-external-symbol class PhpParser\Node\Stmt\TraitUse
 * @phutil-external-symbol class PhpParser\Node\Stmt\TryCatch
 * @phutil-external-symbol class PhpParser\Node\Stmt\While_
 */
final class ArcanistBraceFormattingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 24;

  public function getLintName() {
    return pht('Brace Placement');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
      if ($node->isAbstract()) {
        return;
      }

      $end = $node->getEndTokenPos();

      if ($node->params) {
        $start = last($node->params)->getEndTokenPos();
      } else {
        $start = $node->name->getEndTokenPos();
      }

      $this->lintBraceSpacing($start, $end, $token_stream);
    } else if (
      $node instanceof PhpParser\Node\Stmt\Function_ ||
      $node instanceof PhpParser\Node\Expr\Closure) {

      $end = $node->getEndTokenPos();

      if ($node->params) {
        $start = last($node->params)->getEndTokenPos();
      } else {
        $start = $node->getStartTokenPos();
      }

      $this->lintBraceSpacing($start, $end, $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\Property) {
      if (!$node->hooks) {
        return;
      }

      $start = last($node->props)->getEndTokenPos();
      $end = $node->getEndTokenPos();

      $this->lintBraceSpacing($start, $end, $token_stream);
    } else if ($node instanceof PhpParser\Node\PropertyHook) {
      if (!is_array($node->body)) {
        return;
      }

      $end = $node->getEndTokenPos();
      $start = $node->name->getEndTokenPos();

      $this->lintBraceSpacing($start, $end, $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\For_) {
      $end = $node->getEndTokenPos();
      if ($node->loop) {
        $start = last($node->loop)->getEndTokenPos();
      } else if ($node->cond) {
        $start = last($node->cond)->getEndTokenPos();
      } else if ($node->init) {
        $start = last($node->init)->getEndTokenPos();
      } else {
        $start = $node->getStartTokenPos();
      }

      $opening_brace_max_pos = false;
      if ($node->stmts) {
        $opening_brace_max_pos = head($node->stmts)->getStartTokenPos();
      }

      $this->lintOptionalBraces(
        $start,
        $end,
        $opening_brace_max_pos,
        $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\Foreach_) {
      $end = $node->getEndTokenPos();
      $start = $node->valueVar->getEndTokenPos();

      $opening_brace_max_pos = false;
      if ($node->stmts) {
        $opening_brace_max_pos = head($node->stmts)->getStartTokenPos();
      }

      $this->lintOptionalBraces(
        $start,
        $end,
        $opening_brace_max_pos,
        $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\While_) {
      $end = $node->getEndTokenPos();
      $start = $node->cond->getEndTokenPos();

      $opening_brace_max_pos = false;
      if ($node->stmts) {
        $opening_brace_max_pos = head($node->stmts)->getStartTokenPos();
      }

      $this->lintOptionalBraces(
        $start,
        $end,
        $opening_brace_max_pos,
        $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\Do_) {
      $end = $node->cond->getEndTokenPos();
      $start = $node->getStartTokenPos();

      $opening_brace_max_pos = false;
      if ($node->stmts) {
        $opening_brace_max_pos = head($node->stmts)->getStartTokenPos();
      }

      $this->lintOptionalBraces(
        $start,
        $end,
        $opening_brace_max_pos,
        $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\If_) {
      $this->lintIf($node, $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\TryCatch) {
      $this->lintTryCatch($node, $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\Switch_) {
      $start = $node->cond->getEndTokenPos();
      if ($node->cases) {
        $end = last($node->cases)->getEndTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }

      $this->lintBraceSpacing($start, $end, $token_stream);
    } else if ($node instanceof PhpParser\Node\Expr\Match_) {
      $start = $node->cond->getEndTokenPos();
      $end = last($node->arms)->getEndTokenPos();

      $this->lintBraceSpacing($start, $end, $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\Class_) {
      $end = $node->getEndTokenPos();

      if ($node->implements) {
        $start = last($node->implements)->getEndTokenPos();
      } else if ($node->extends) {
        $start = $node->extends->getEndTokenPos();
      } else if ($node->name) {
        $start = $node->name->getEndTokenPos();
      } else {
        $start = $node->getStartTokenPos();
      }

      $this->lintBraceSpacing($start, $end, $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\Interface_) {
      $end = $node->getEndTokenPos();

      if ($node->extends) {
        $start = last($node->extends)->getEndTokenPos();
      } else {
        $start = $node->name->getEndTokenPos();
      }

      $this->lintBraceSpacing($start, $end, $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\Trait_) {
      $start = $node->name->getEndTokenPos();
      $end = $node->getEndTokenPos();

      $this->lintBraceSpacing($start, $end, $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\Enum_) {
      $end = $node->getEndTokenPos();

      if ($node->implements) {
        $start = last($node->implements)->getEndTokenPos();
      } else if ($node->scalarType) {
        $start = $node->scalarType->getEndTokenPos();
      } else {
        $start = $node->name->getEndTokenPos();
      }

      $this->lintBraceSpacing($start, $end, $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\Declare_) {
      if ($node->stmts === null) {
        return;
      }

      $end = $node->getEndTokenPos();
      if ($node->declares) {
        $start = last($node->declares)->getEndTokenPos();
      } else {
        $start = $node->getStartTokenPos();
      }

      $opening_brace_max_pos = false;
      if ($node->stmts) {
        $opening_brace_max_pos = head($node->stmts)->getStartTokenPos();
      }

      $this->lintOptionalBraces(
        $start,
        $end,
        $opening_brace_max_pos,
        $token_stream);
    } else if ($node instanceof PhpParser\Node\Stmt\TraitUse) {
      $end = $node->getEndTokenPos();
      $start = last($node->traits)->getEndTokenPos();

      $this->lintBraceSpacing($start, $end, $token_stream);
    } else {
      return;
    }
  }

  private function lintBraceSpacing(
    int $start,
    int $end,
    array $token_stream) {

    $opening_brace = $this->getOpeningBrace($start, $end, $token_stream);

    if (!$opening_brace) {
      return;
    }

    $leading = $this->getNonsemanticTokensBefore(
      $opening_brace,
      $token_stream);

    if (!$leading) {
      $this->raiseLintAtToken(
        $token_stream[$opening_brace],
        pht(
          'Put opening braces on the same line as control statements and '.
          'declarations, with a single space before them.'),
        ' {');
    } else if (count($leading) === 1) {
      $first = head($leading);

      if ($first->text !== ' ') {
        $this->raiseLintAtToken(
          $first,
          pht(
            'Put opening braces on the same line as control statements and '.
            'declarations, with a single space before them.'),
          ' ');
      }
    }
  }

  /**
   * @param int $start
   * @param int $end
   * @param int|false $opening_brace_max_pos
   * @param array<PhpParser\Token> $token_stream
   */
  private function lintOptionalBraces(
    int $start,
    int $end,
    $opening_brace_max_pos,
    array $token_stream) {

    $has_braces = $this->getOpeningBrace(
      $start,
      $opening_brace_max_pos ?: $end,
      $token_stream);

    if (!$has_braces) {
      $this->raiseLintAtToken(
        $token_stream[$opening_brace_max_pos],
        pht('Use braces to surround a statement block.'));

      return;
    }

    $this->lintBraceSpacing($start, $end, $token_stream);
  }

  private function lintIf(
    PhpParser\Node\Stmt\If_ $if,
    array $token_stream) {

    $start = $if->cond->getEndTokenPos();

    if ($if->elseifs) {
      $end = head($if->elseifs)->getStartTokenPos();
    } else if ($if->else) {
      $end = $if->else->getStartTokenPos();
    } else {
      $end = $if->getEndTokenPos();
    }

    $opening_brace_max_pos = false;
    if ($if->stmts) {
      $opening_brace_max_pos = head($if->stmts)->getStartTokenPos();
    }

    $this->lintOptionalBraces(
      $start,
      $end,
      $opening_brace_max_pos,
      $token_stream);

    $last_has_braces = $this->getOpeningBrace($start, $end, $token_stream);
    $last = $this->getClosingBrace($end, $token_stream);

    foreach ($if->elseifs as $elseif) {
      if ($last_has_braces) {
        $this->lintChain($last, $elseif->getStartTokenPos(), $token_stream);
        $last = $this->getClosingBrace($end, $token_stream);
      }
      $last_has_braces = $this->getOpeningBrace($start, $end, $token_stream);

      $end = $elseif->getEndTokenPos();
      $start = $elseif->cond->getEndTokenPos();

      $opening_brace_max_pos = false;
      if ($elseif->stmts) {
        $opening_brace_max_pos = head($elseif->stmts)->getStartTokenPos();
      }

      $this->lintOptionalBraces(
        $start,
        $end,
        $opening_brace_max_pos,
        $token_stream);
    }

    if ($if->else) {
      if ($last_has_braces) {
        $this->lintChain($last, $if->else->getStartTokenPos(), $token_stream);
      }

      if (
        count($if->else->stmts) === 1 &&
        $if->else->stmts[0] instanceof PhpParser\Node\Stmt\If_) {

        return;
      }

      $start = $if->else->getStartTokenPos();
      $end = $if->else->getEndTokenPos();

      $opening_brace_max_pos = false;
      if ($if->else->stmts) {
        $opening_brace_max_pos = head($if->else->stmts)->getStartTokenPos();
      }

      $this->lintOptionalBraces(
        $start,
        $end,
        $opening_brace_max_pos,
        $token_stream);
    }
  }

  private function lintTryCatch(
    PhpParser\Node\Stmt\TryCatch $try_catch,
    array $token_stream) {

    $start = $try_catch->getStartTokenPos();

    if ($try_catch->catches) {
      $end = head($try_catch->catches)->getStartTokenPos();
    } else if ($try_catch->finally) {
      $end = $try_catch->finally->getStartTokenPos();
    } else {
      $end = $try_catch->getEndTokenPos();
    }

    $opening_brace_max_pos = false;
    if ($try_catch->stmts) {
      $opening_brace_max_pos = head($try_catch->stmts)->getStartTokenPos();
    }

    $this->lintOptionalBraces(
      $start,
      $end,
      $opening_brace_max_pos,
      $token_stream);

    foreach ($try_catch->catches as $catch) {
      $end = $catch->getEndTokenPos();

      if ($catch->var) {
        $start = $catch->var->getEndTokenPos();
      } else {
        $start = head($catch->types)->getEndTokenPos();
      }

      $opening_brace_max_pos = false;
      if ($catch->stmts) {
        $opening_brace_max_pos = head($catch->stmts)->getStartTokenPos();
      }

      $this->lintOptionalBraces(
        $start,
        $end,
        $opening_brace_max_pos,
        $token_stream);
    }

    if ($try_catch->finally) {
      $start = $try_catch->finally->getStartTokenPos();
      $end = $try_catch->finally->getEndTokenPos();

      $opening_brace_max_pos = false;
      if ($try_catch->finally->stmts) {
        $opening_brace_max_pos = head($try_catch->finally->stmts)
          ->getStartTokenPos();
      }

      $this->lintOptionalBraces(
        $start,
        $end,
        $opening_brace_max_pos,
        $token_stream);
    }
  }

  private function lintChain(
    int $left_end,
    int $right_start,
    array $token_stream) {

    $tokens_in_between = array();

    for ($i = $left_end; $i < $right_start; $i++) {
      $token = $token_stream[$i];

      if ($token->is('}')) {
        continue;
      }

      if ($token->isIgnorable()) {
        $tokens_in_between[] = $token;
      }
    }

    if (!$tokens_in_between) {
      $this->raiseLintAtToken(
        $token_stream[$right_start],
        pht(
          'Put closing braces on the same line as control statements and '.
          'declarations, with a single space after them.'),
        ' '.$token_stream[$right_start]->text);
    } else if (count($tokens_in_between) === 1) {
      $tokens_in_between = head($tokens_in_between);
      if ($tokens_in_between->text !== ' ') {
        $this->raiseLintAtToken(
          $tokens_in_between,
          pht(
            'Put closing braces on the same line as control statements and '.
            'declarations, with a single space after them.'),
          ' ');
        }
      }
  }

  private function getOpeningBrace(int $start, int $end, array $token_stream) {
    for ($i = $start; $i < $end; $i++) {
      if ($token_stream[$i]->is('{')) {
        return $i;
      }
    }

    return false;
  }

  private function getClosingBrace(int $start, array $token_stream) {
    for ($i = $start; $i > 0; $i--) {
      if ($token_stream[$i]->is('}')) {
        return $i;
      }
    }

    return false;
  }

}
