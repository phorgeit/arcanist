<?php

/**
 * Find cases where a `foreach` loop is being iterated using a variable
 * reference and the same variable is used outside of the loop without calling
 * `unset()` or reassigning the variable to another variable reference.
 *
 *   COUNTEREXAMPLE
 *   foreach ($ar as &$a) {
 *     // ...
 *   }
 *   $a = 1; // <-- Raises an error for using $a
 *
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 * @phutil-external-symbol class PhpParser\Node\Expr\ArrowFunction
 * @phutil-external-symbol class PhpParser\Node\Stmt\For_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Foreach_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 * @phutil-external-symbol class PhpParser\Node\Expr\Closure
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignRef
 * @phutil-external-symbol class PhpParser\Node\Stmt\Unset_
 * @phutil-external-symbol class PhpParser\Node\Expr\List_
 */
final class ArcanistReusedIteratorReferencePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 39;

  private $cache = array();

  public function getLintName() {
    return pht('Reuse of Iterator References');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if (
      !($node instanceof PhpParser\Node\FunctionLike) ||
      $node instanceof PhpParser\Node\Expr\ArrowFunction ||
      !$node->getStmts()) {
      return;
    }

    // Iterate the nodes in this function, collecting all the ones we need
    // ahead of time. Because we need nodes in the current scope, we
    // cannot leverage the built-in caching of PhpParserAst.
    $body_ast = PhpParserAst::newPartialAst($node->getStmts());
    $this->cache = $body_ast->findNodesOfKinds(
      array(
        PhpParser\Node\Stmt\Foreach_::class,
        PhpParser\Node\Stmt\Unset_::class,
        PhpParser\Node\Expr\AssignRef::class,
      ),
      array(
        PhpParser\Node\Stmt\Class_::class,
        PhpParser\Node\Expr\Closure::class,
        PhpParser\Node\Stmt\Function_::class,
      ));

    $declarations = array();
    $unset_vars = array();
    $reference_assignments = array();

    $foreaches = $this->getNodesOfType(
      PhpParser\Node\Stmt\Foreach_::class);

    foreach ($foreaches as $foreach) {
      if ($foreach->valueVar instanceof PhpParser\Node\Expr\List_) {
        $items = $this->unnestList($foreach->valueVar);

        foreach ($items as $item) {
          if ($item->byRef && is_string($item->value->name)) {
            $declarations[$item->value->name][] = array(
              $foreach->getStartFilePos(),
              $foreach->getEndFilePos(),
            );
          }
        }
      } else if ($foreach->byRef) {
        if (
          !($foreach->valueVar instanceof PhpParser\Node\Expr\Variable) ||
          !is_string($foreach->valueVar->name)) {

          continue;
        }

        $declarations[$foreach->valueVar->name][] = array(
          $foreach->getStartFilePos(),
          $foreach->getEndFilePos(),
        );
      }
    }

    // If there are no reference declarations in foreach loops,
    // then there's nothing to do
    if (!$declarations) {
      return;
    }

    $unsets = $this->getNodesOfType(
      PhpParser\Node\Stmt\Unset_::class);
    foreach ($unsets as $unset) {
      foreach ($unset->vars as $var) {
        if (
          !($var instanceof PhpParser\Node\Expr\Variable) ||
          !is_string($var->name)) {
          continue;
        }
        $unset_vars[$var->name][] = $unset->getEndFilePos();
      }
    }

    // Allow usage if the reference variable is assigned to another
    // reference variable
    $assign_refs = $this->getNodesOfType(
       PhpParser\Node\Expr\AssignRef::class);
    foreach ($assign_refs as $assign_ref) {
      if (
        !($assign_ref->var instanceof PhpParser\Node\Expr\Variable) ||
        !is_string($assign_ref->var->name) ||
        !($assign_ref->expr instanceof PhpParser\Node\Expr\Variable) ||
        !is_string($assign_ref->expr->name)) {

        continue;
      }

      $reference_assignments[$assign_ref->var->name][] = $assign_ref
        ->var->getStartFilePos();
      // Counts as unsetting a variable
      $unset_vars[$assign_ref->var->name][] = $assign_ref->var->getEndFilePos();
    }

    foreach ($body_ast->findVariablesInScope() as $variable) {
      if (
        !is_string($variable->name) ||
        !isset($declarations[$variable->name])) {
        continue;
      }

      $parent = $variable->getAttribute('parent');
      if ($parent instanceof PhpParser\Node\Stmt\Unset_) {
        continue;
      }

      list($foreach_start) = head($declarations[$variable->name]);

      // If the usage is before the first foreach then that doesn't matter
      if ($foreach_start > $variable->getStartFilePos()) {
        continue;
      }

      foreach ($declarations[$variable->name] as $declaration) {
        list($foreach_start, $foreach_end) = $declaration;

        // Exclude uses of the reference variable within the foreach loop
        if (
          $variable->getStartFilePos() > $foreach_start &&
          $variable->getStartFilePos() < $foreach_end) {

          continue 2;
        }
      }

      // Allow usage if the reference variable is assigned to another
      // reference variable
      foreach (idx($reference_assignments, $variable->name, array()) as $pos) {
        if ($variable->getStartFilePos() === $pos) {
          continue 2;
        }
      }

      foreach (idx($unset_vars, $variable->name, array()) as $unset_pos) {
        if ($variable->getStartFilePos() > $unset_pos) {
          continue 2;
        }
      }

      $this->raiseLintAtNode(
        $variable,
        pht(
          'This variable was used already as a by-reference iterator '.
          'variable. Such variables survive outside the `%s` loop, '.
          'do not reuse.',
          'foreach'));
    }
  }

  /**
   * @param PhpParser\Node\Expr\List_ $list
   * @return Generator<PhpParser\Node\ArrayItem>
   */
  protected function unnestList(PhpParser\Node\Expr\List_ $list) {
    foreach ($list->items as $item) {
      if (!$item instanceof PhpParser\Node) {
        continue;
      } else if ($item->value instanceof PhpParser\Node\Expr\List_) {
        // XHPAST doesn't support yield from.
        foreach ($this->unnestList($item->value) as $nested_item) {
          yield $nested_item;
        }
      } else {
        yield $item;
      }
    }
  }

  /**
   * @template TNode as PhpParser\Node
   *
   * @param class-string<TNode> $kind
   * @return Generator<TNode>
   */
  private function getNodesOfType(string $kind) {
    foreach ($this->cache as $node) {
      if ($node instanceof $kind) {
        yield $node;
      }
    }
  }

}
