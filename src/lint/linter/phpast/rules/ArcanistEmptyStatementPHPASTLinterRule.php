<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\Closure
 * @phutil-external-symbol class PhpParser\Node\PropertyHook
 * @phutil-external-symbol class PhpParser\Node\Stmt\Block
 * @phutil-external-symbol class PhpParser\Node\Stmt\Catch_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Stmt\Declare_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Do_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Else_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ElseIf_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Enum_
 * @phutil-external-symbol class PhpParser\Node\Stmt\For_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Foreach_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Finally_
 * @phutil-external-symbol class PhpParser\Node\Stmt\If_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Interface_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Namespace_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Switch_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Trait_
 * @phutil-external-symbol class PhpParser\Node\Stmt\TryCatch
 * @phutil-external-symbol class PhpParser\Node\Stmt\While_
 */
final class ArcanistEmptyStatementPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 47;

  public function getLintName() {
    return pht('Empty Block Statement');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\Closure) {
      if ($node->stmts) {
        return;
      }

      $end = $node->getEndTokenPos();
      if ($node->returnType) {
        $start = $node->returnType->getEndTokenPos();
      } else if ($node->uses) {
        $start = last($node->uses)->getEndTokenPos();
      } else if ($node->params) {
        $start = last($node->params)->getEndTokenPos();
      } else {
        $start = $node->getStartTokenPos();
      }
    } else if (
      $node instanceof PhpParser\Node\Stmt\Block ||
      $node instanceof PhpParser\Node\Stmt\Finally_ ||
      $node instanceof PhpParser\Node\Stmt\Else_) {

      if ($node->stmts) {
        return;
      }

      $start = $node->getStartTokenPos();
      $end = $node->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\Catch_) {
      if ($node->stmts) {
        return;
      }

      $end = $node->getEndTokenPos();
      if ($node->var) {
        $start = $node->var->getEndTokenPos();
      } else {
        $start = last($node->types)->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Stmt\Class_) {
      if ($node->stmts) {
        return;
      }

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
    } else if ($node instanceof PhpParser\Node\Stmt\Interface_) {
      if ($node->stmts) {
        return;
      }

      $end = $node->getEndTokenPos();
      if ($node->extends) {
        $start = last($node->extends)->getEndTokenPos();
      } else {
        $start = $node->name->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Stmt\Trait_) {
      if ($node->stmts) {
        return;
      }

      $end = $node->getEndTokenPos();
      $start = $node->name->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\Enum_) {
      if ($node->stmts) {
        return;
      }

      $end = $node->getEndTokenPos();
      if ($node->scalarType) {
        $start = $node->scalarType->getEndTokenPos();
      } else {
        $start = $node->name->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
      // Null indicates that the method doesn't support statements.
      // This applies to abstract methods or methods in interfaces.
      if ($node->stmts || $node->stmts === null) {
        return;
      }

      $end = $node->getEndTokenPos();
      if ($node->returnType) {
        $start = $node->returnType->getEndTokenPos();
      } else if ($node->params) {
        $start = last($node->params)->getEndTokenPos();
      } else {
        $start = $node->name->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Stmt\Declare_) {
      // Null indicates that the declare is file scoped.
      if ($node->stmts || $node->stmts === null) {
        return;
      }

      $end = $node->getEndTokenPos();
      $start = last($node->declares)->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\Do_) {
      if ($node->stmts) {
        return;
      }

      $start = $node->getStartTokenPos();
      $end = $node->cond->getStartTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\ElseIf_) {
      if ($node->stmts) {
        return;
      }

      $end = $node->getEndTokenPos();
      $start = $node->cond->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\For_) {
      if ($node->stmts) {
        return;
      }

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
    } else if ($node instanceof PhpParser\Node\Stmt\Foreach_) {
      if ($node->stmts) {
        return;
      }

      $end = $node->getEndTokenPos();
      $start = $node->valueVar->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\Function_) {
      if ($node->stmts) {
        return;
      }

      $end = $node->getEndTokenPos();
      if ($node->returnType) {
        $start = $node->returnType->getEndTokenPos();
      } else if ($node->params) {
        $start = last($node->params)->getEndTokenPos();
      } else {
        $start = $node->name->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Stmt\If_) {
      if ($node->stmts) {
        return;
      }

      $start = $node->cond->getEndTokenPos();
      if ($node->elseifs) {
        $end = head($node->elseifs)->getStartTokenPos();
      } else if ($node->else) {
        $end = $node->else->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Stmt\Namespace_) {
      $kind = $node->getAttribute('kind');

      if (
        $node->stmts ||
        $kind === PhpParser\Node\Stmt\Namespace_::KIND_SEMICOLON) {

        return;
      }

      $end = $node->getEndTokenPos();
      if ($node->name) {
        $start = $node->name->getStartTokenPos();
      } else {
        $start = $node->getStartTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Stmt\TryCatch) {
      if ($node->stmts) {
        return;
      }

      $start = $node->getStartTokenPos();
      if ($node->catches) {
        $end = head($node->catches)->getStartTokenPos();
      } else if ($node->finally) {
        $end = $node->finally->getStartTokenPos();
      } else {
        $end = $node->getEndTokenPos();
      }
    } else if ($node instanceof PhpParser\Node\Stmt\While_) {
      if ($node->stmts) {
        return;
      }

      $end = $node->getEndTokenPos();
      $start = $node->cond->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\Stmt\Switch_) {
      if ($node->cases) {
        return;
      }

      $start = $node->cond->getEndTokenPos();
      $end = $node->getEndTokenPos();
    } else if ($node instanceof PhpParser\Node\PropertyHook) {
      if ($node->body) {
        return;
      }

      $end = $node->getEndTokenPos();
      if ($node->params) {
        $start = last($node->params)->getEndTokenPos();
      } else {
        $start = $node->name->getEndTokenPos();
      }
    } else {
      return;
    }

    $between = array();
    $in_between = false;
    $offset = -1;

    for ($i = $start; $i < $end; $i++) {
      if ($token_stream[$i]->is('{')) {
        $in_between = true;
        $offset = $token_stream[$i]->pos;
        continue;
      }

      if ($token_stream[$i]->is('}')) {
        break;
      }

      if ($token_stream[$i]->is(array(T_COMMENT, T_DOC_COMMENT))) {
        return;
      }

      if ($in_between) {
        $between[] = $token_stream[$i];
      }
    }

    if (!$between) {
      return;
    }

    $original = '{'.implode('', ppull($between, 'text')).'}';

    $this->raiseLintAtOffset(
      $offset,
      pht(
        "Braces for an empty block statement shouldn't contain only ".
        'whitespace.'),
      $original,
      '{}');
  }

}
