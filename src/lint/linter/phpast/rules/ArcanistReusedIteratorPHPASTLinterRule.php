<?php

/**
 * Find cases where loops get nested inside each other but use the same
 * iterator variable. For example:
 *
 *   COUNTEREXAMPLE
 *   foreach ($list as $thing) {
 *     foreach ($stuff as $thing) { // <-- Raises an error for reuse of $thing
 *       // ...
 *     }
 *   }
 *
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\For_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Foreach_
 * @phutil-external-symbol class PhpParser\Node\Expr
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignRef
 * @phutil-external-symbol class PhpParser\Node\Expr\Assign
 * @phutil-external-symbol class PhpParser\Node\Expr\List_
 */
final class ArcanistReusedIteratorPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 23;

  public function getLintName() {
    return pht('Reuse of Iterator Variable');
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    $for_loops = $ast->findNodesOfKind(
      PhpParser\Node\Stmt\For_::class);

    $foreach_loops = $ast->findNodesOfKind(
      PhpParser\Node\Stmt\Foreach_::class);

    $used_vars = array();

    foreach ($for_loops as $for_loop) {
      $var_map = array();

      foreach ($this->extractAssignments($for_loop->init) as $var) {
        $var_map[$var->name] = $var;
      }

      foreach ($this->extractAssignments($for_loop->cond) as $var) {
        $var_map[$var->name] = $var;
      }

      foreach ($this->extractAssignments($for_loop->init) as $var) {
        $var_map[$var->name] = $var;
      }

      $used_vars[$for_loop->getStartTokenPos()] = $var_map;
    }

    foreach ($foreach_loops as $foreach_loop) {
      $var_map = array();

      foreach ($this->unnestLoopVariable($foreach_loop->valueVar) as $var) {
        $var_map[$var->name] = $var;
      }

      if ($foreach_loop->keyVar) {
        foreach ($this->unnestLoopVariable($foreach_loop->keyVar) as $var) {
          $var_map[$var->name] = $var;
        }
      }

      $used_vars[$foreach_loop->getStartTokenPos()] = $var_map;
    }

    $all_loops = array_merge($for_loops, $foreach_loops);
    foreach ($all_loops as $loop) {
      $child_loops = PhpParserAst::newPartialAst($loop->stmts)
        ->findNodesOfKinds(
          array(
            PhpParser\Node\Stmt\For_::class,
            PhpParser\Node\Stmt\Foreach_::class,
          ));

      $outer_vars = $used_vars[$loop->getStartTokenPos()];
      foreach ($child_loops as $inner_loop) {
        $inner_vars = $used_vars[$inner_loop->getStartTokenPos()];
        $shared = array_intersect_key($outer_vars, $inner_vars);
        if ($shared) {
          $shared_desc = implode(', ', array_keys($shared));
          $message = $this->raiseLintAtNode(
            $inner_loop,
            pht(
              'This loop reuses iterator variables (%s) from an '.
              'outer loop. You might be clobbering the outer iterator. '.
              'Change the inner loop to use a different iterator name.',
              $shared_desc));

          $locations = array();
          foreach ($shared as $var) {
            $locations[] = $this->getOtherLocation($var->getStartFilePos());
          }
          $message->setOtherLocations($locations);
        }
      }
    }
  }

  /**
   * @param PhpParser\Node\Expr $expr
   * @return Generator<PhpParser\Node\Expr\Variable>
   */
  private function unnestLoopVariable(PhpParser\Node\Expr $expr) {
    if ($expr instanceof PhpParser\Node\Expr\List_) {
      foreach ($expr->items as $item) {
        if (!($item instanceof PhpParser\Node)) {
          continue;
        }

        // XHPAST doesn't support yield from.
        foreach ($this->unnestLoopVariable($item->value) as $variable) {
          yield $variable;
        }
      }
    } else if (
      $expr instanceof PhpParser\Node\Expr\Variable &&
      is_string($expr->name)) {
      yield $expr;
    }
  }

  /**
   * @param array<PhpParser\Node\Expr> $expressions
   * @return Generator<PhpParser\Node\Expr\Variable>
   */
  private function extractAssignments(array $expressions) {
    foreach ($expressions as $expression) {
      if (
        $expression instanceof PhpParser\Node\Expr\Assign ||
        $expression instanceof PhpParser\Node\Expr\AssignRef) {

        // XHPAST doesn't support yield from.
        foreach ($this->unnestLoopVariable($expression->var) as $var) {
          yield $var;
        }

        // XHPAST doesn't support yield from.
        foreach ($this->extractAssignments(array($expression->expr)) as $var) {
          yield $var;
        }
      }
    }
  }

}
