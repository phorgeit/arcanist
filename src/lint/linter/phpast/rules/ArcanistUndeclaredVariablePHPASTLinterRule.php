<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassLike
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 * @phutil-external-symbol class PhpParser\Node\Expr\ArrowFunction
 * @phutil-external-symbol class PhpParser\Node\Expr\Closure
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 * @phutil-external-symbol class PhpParser\Node\StaticVar
 * @phutil-external-symbol class PhpParser\Node\Stmt\Global_
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 * @phutil-external-symbol class PhpParser\Node\Stmt\Catch_
 * @phutil-external-symbol class PhpParser\Node\Expr\Assign
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignRef
 * @phutil-external-symbol class PhpParser\Node\Expr\List_
 * @phutil-external-symbol class PhpParser\Node\Expr\Isset_
 * @phutil-external-symbol class PhpParser\Node\Expr\Empty_
 * @phutil-external-symbol class PhpParser\Node\Expr\ArrayDimFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Coalesce
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Stmt\For_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Foreach_
 */
final class ArcanistUndeclaredVariablePHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 5;

  private $cache = array();

  public function getLintName() {
    return pht('Use of Undeclared Variable');
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
        PhpParser\Node\StaticVar::class,
        PhpParser\Node\Stmt\Global_::class,
        PhpParser\Node\Stmt\Catch_::class,
        PhpParser\Node\Expr\Assign::class,
        PhpParser\Node\Expr\AssignRef::class,
        PhpParser\Node\Expr\Isset_::class,
        PhpParser\Node\Expr\Empty_::class,
        PhpParser\Node\Expr\BinaryOp\Coalesce::class,
        PhpParser\Node\Expr\FuncCall::class,
        PhpParser\Node\Stmt\Foreach_::class,
      ),
      array(
        PhpParser\Node\Stmt\Class_::class,
        PhpParser\Node\Expr\Closure::class,
        PhpParser\Node\Stmt\Function_::class,
      ));

    // These things declare variables in a function:
    //    Explicit parameters
    //    Closure `use` parameters
    //    Assignment
    //    Assignment via list()
    //    Static
    //    Global
    //    Lexical vars
    //    Builtins ($this)
    //    foreach()
    //    catch
    //
    // These things make lexical scope unknowable:
    //    Use of extract()
    //    Assignment to variable variables ($$x)
    //    Global with variable variables
    //
    // These things don't count as "using" a variable:
    //    isset()
    //    empty()
    //    null-coalescing using ??
    //    Static class variables
    //
    // The general approach here is to find each function/method declaration,
    // then:
    //
    //  1. Identify all the variable declarations, and where they first occur
    //     in the function/method declaration.
    //  2. Identify all the uses that don't really count (as above).
    //  3. Everything else must be a use of a variable.
    //  4. For each variable, check if any uses occur before the declaration
    //     and warn about them.
    //
    // We also keep track of where lexical scope becomes unknowable (e.g.,
    // because the function calls extract() or uses dynamic variables,
    // preventing us from keeping track of which variables are defined) so we
    // can stop issuing warnings after that.

    // We keep track of the first offset where scope becomes unknowable, and
    // silence any warnings after that. Default it to INT_MAX so we can min()
    // it later to keep track of the first problem we encounter.
    $scope_destroyed_at = PHP_INT_MAX;

    $declarations = array(
      'this' => 0,
    ) + array_fill_keys($this->getSuperGlobalNames(), 0);
    $declaration_tokens = array();
    $exclude_tokens = array();
    $vars = array();

    foreach ($node->getParams() as $param) {
      if (!is_string($param->var->name)) {
        continue;
      }
      $vars[] = $param->var;
    }

    if ($node instanceof PhpParser\Node\Expr\Closure) {
      foreach ($node->uses as $use) {
        if (!is_string($use->var->name)) {
          continue;
        }
        $vars[] = $use->var;
      }
    }

    $static_vars = $this->getNodesOfType(PhpParser\Node\StaticVar::class);
    foreach ($static_vars as $static_var) {
      if (!is_string($static_var->var->name)) {
        continue;
      }
      $vars[] = $static_var->var;
    }

    $global_vars = $this->getNodesOfType(PhpParser\Node\Stmt\Global_::class);
    foreach ($global_vars as $global_var) {
      foreach ($global_var->vars as $var) {
        if (!($var instanceof PhpParser\Node\Expr\Variable)) {
          continue;
        }

        if (!is_string($var->name)) {
          // Dynamic global variable, i.e. "global $$x;".
          $scope_destroyed_at = min(
            $scope_destroyed_at,
            $var->getStartFilePos());
          // An error is raised elsewhere, no need to raise here.
          continue;
        }

        $vars[] = $var;
      }
    }

    $catches = $this->getNodesOfType(PhpParser\Node\Stmt\Catch_::class);
    foreach ($catches as $catch) {
      if (!$catch->var || !is_string($catch->var->name)) {
        continue;
      }
      $vars[] = $catch->var;
    }

    $assignments = $this->getNodesOfTypes(
      array(
        PhpParser\Node\Expr\Assign::class,
        PhpParser\Node\Expr\AssignRef::class,
      ));
    foreach ($assignments as $assignment) {
      if ($assignment->var instanceof PhpParser\Node\Expr\Variable) {
        if (!is_string($assignment->var->name)) {
          $scope_destroyed_at = min(
            $scope_destroyed_at,
            $assignment->var->getStartFilePos());
          // No need to raise here since we raise an error elsewhere.
          continue;
        }
        $vars[] = $assignment->var;
      } else if ($assignment->var instanceof PhpParser\Node\Expr\List_) {
        $variables = PhpParserAst::newPartialAst($assignment->var->items)
          ->findNodesOfKind(PhpParser\Node\Expr\Variable::class);

        foreach ($variables as $variable) {
          if (!is_string($variable->name)) {
            $scope_destroyed_at = min(
              $scope_destroyed_at,
              $variable->getStartFilePos());
            // No need to raise here since we raise an error elsewhere.
            continue;
          }
          $vars[] = $variable;
        }
      }
    }

    $issets_and_emptys = $this->getNodesOfTypes(
      array(
        PhpParser\Node\Expr\Isset_::class,
        PhpParser\Node\Expr\Empty_::class,
      ));

    foreach ($issets_and_emptys as $isset_or_empty) {
      if ($isset_or_empty instanceof PhpParser\Node\Expr\Empty_) {
        $variables = array($isset_or_empty->expr);
      } else {
        $variables = $isset_or_empty->vars;
      }

      foreach ($variables as $variable) {
        if ($variable instanceof PhpParser\Node\Expr\ArrayDimFetch) {
          $dim_left = $variable;

          do {
            $dim_left = $dim_left->var;
          } while ($dim_left instanceof PhpParser\Node\Expr\ArrayDimFetch);

          if (
            $dim_left instanceof PhpParser\Node\Expr\Variable &&
            is_string($dim_left->name)) {

            $exclude_tokens[$variable->getStartTokenPos()] = true;
          }
        } else if (
          $variable instanceof PhpParser\Node\Expr\Variable &&
          is_string($variable->name)) {

          $exclude_tokens[$variable->getStartTokenPos()] = true;
        }
      }
    }

    $null_coalesces = $this->getNodesOfType(
      PhpParser\Node\Expr\BinaryOp\Coalesce::class);
    foreach ($null_coalesces as $null_coalesce) {
      if (
        !($null_coalesce->left instanceof PhpParser\Node\Expr\Variable) ||
        !is_string($null_coalesce->left->name)) {
        continue;
      }

      $exclude_tokens[$null_coalesce->left->getStartTokenPos()] = true;
    }

    $function_calls = $this->getNodesOfType(
      PhpParser\Node\Expr\FuncCall::class);
    foreach ($function_calls as $call) {
      if (
        !($call->name instanceof PhpParser\Node\Name) ||
        $call->name->toLowerString() !== 'extract') {
        continue;
      }

      $scope_destroyed_at = min(
        $scope_destroyed_at,
        $call->getStartFilePos());
    }

    // Now we have every declaration except foreach(), handled below. Build
    // two maps, one which just keeps track of which tokens are part of
    // declarations ($declaration_tokens) and one which has the first offset
    // where a variable is declared ($declarations).

    foreach ($vars as $var) {
      $declarations[$var->name] = min(
        idx($declarations, $var->name, PHP_INT_MAX),
        $var->getStartFilePos());
      $declaration_tokens[$var->getStartTokenPos()] = true;
    }

    // Find all the variables in scope, and figure out where they are used.
    // We want to find foreach() iterators which are both declared before and
    // used after the foreach() loop.

    $uses = array();

    $all = array();

    foreach ($body_ast->findVariablesInScope() as $var) {

      // Be strict since it's easier; we don't let you reuse an iterator you
      // declared before a loop after the loop, even if you're just assigning
      // to it.

      $uses[$var->name][$var->getStartTokenPos()] = $var->getStartFilePos();

      if (isset($declaration_tokens[$var->getStartTokenPos()])) {
        // We know this is part of a declaration, so it's fine.
        continue;
      }
      if (isset($exclude_tokens[$var->getStartTokenPos()])) {
        // We know this is part of isset() or similar, so it's fine.
        continue;
      }

      $all[$var->getStartFilePos()] = $var->name;
    }

    // Do foreach() last, we want to handle implicit redeclaration of a
    // variable already in scope since this probably means we're ovewriting a
    // local.

    // NOTE: Processing foreach expressions in order allows programs which
    // reuse iterator variables in other foreach() loops -- this is fine. We
    // have a separate warning to prevent nested loops from reusing the same
    // iterators.
    $foreaches = $this->getNodesOfType(PhpParser\Node\Stmt\Foreach_::class);

    $all_foreach_vars = array();
    foreach ($foreaches as $foreach) {
      $foreach_vars = array();

      $foreach_end = $foreach->getEndFilePos();

      if (
        $foreach->keyVar instanceof PhpParser\Node\Expr\Variable &&
        is_string($foreach->keyVar->name)) {

        $foreach_vars[] = $foreach->keyVar;
      }

      if ($foreach->valueVar instanceof PhpParser\Node\Expr\Variable) {
        if (is_string($foreach->valueVar->name)) {
          $foreach_vars[] = $foreach->valueVar;
        }
      } else if ($foreach->valueVar instanceof PhpParser\Node\Expr\List_) {
        foreach ($this->unnestList($foreach->valueVar) as $variable) {
          if (is_string($variable->name)) {
            $foreach_vars[] = $variable;
          }
        }
      }

      // Remove all uses of the iterators inside of the foreach() loop from
      // the $uses map.

      foreach ($foreach_vars as $var) {
        $offset = $var->getStartFilePos();

        foreach ($uses[$var->name] as $token_start_pos => $use_offset) {
          if ($use_offset >= $offset && $use_offset < $foreach_end) {
            unset($uses[$var->name][$token_start_pos]);
          }
        }

        $all_foreach_vars[] = $var;
      }
    }

    foreach ($all_foreach_vars as $var) {
      // This is a declaration, exclude it from the "declare variables prior
      // to use" check below.
      unset($all[$var->getStartFilePos()]);

      $vars[] = $var;
    }

    // Now rebuild declarations to include foreach().
    foreach ($vars as $var) {
      $declarations[$var->name] = min(
        idx($declarations, $var->name, PHP_INT_MAX),
        $var->getStartFilePos());
      $declaration_tokens[$var->getStartTokenPos()] = true;
    }

    // Issue a warning for every variable token, unless it appears in a
    // declaration, we know about a prior declaration, we have explicitly
    // excluded it, or scope has been made unknowable before it appears.

    $issued_warnings = array();
    foreach ($all as $offset => $variable_name) {
      if ($offset >= $scope_destroyed_at) {
        // This appears after an extract() or $$var so we have no idea
        // whether it's legitimate or not. We raised a harshly-worded warning
        // when scope was made unknowable, so just ignore anything we can't
        // figure out.
        continue;
      }
      if ($offset >= idx($declarations, $variable_name, PHP_INT_MAX)) {
        // The use appears after the variable is declared, so it's fine.
        continue;
      }
      if (!empty($issued_warnings[$variable_name])) {
        // We've already issued a warning for this variable so we don't need
        // to issue another one.
        continue;
      }

      $this->raiseLintAtOffset(
        $offset,
        pht(
          'Declare variables prior to use (even if you are passing them '.
          'as reference parameters). You may have misspelled this '.
          'variable name.'),
        '$'.$variable_name);
      $issued_warnings[$variable_name] = true;
    }
  }

  /**
   * @param PhpParser\Node\Expr\List_ $list
   * @return Generator<PhpParser\Node\Expr\Variable>
   */
  private function unnestList(PhpParser\Node\Expr\List_ $list) {
    foreach ($list->items as $item) {
      if (!$item instanceof PhpParser\Node) {
        continue;
      } else if ($item->value instanceof PhpParser\Node\Expr\Variable) {
        yield $item->value;
      } else if ($item->value instanceof PhpParser\Node\Expr\List_) {
        // XHPAST doesn't support yield from.
        foreach ($this->unnestList($item->value) as $variable) {
          yield $variable;
        }
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

  /**
   * @template TNode as PhpParser\Node
   *
   * @param array<class-string<TNode>> $kinds
   * @return Generator<PhpParser\Node>
   */
  private function getNodesOfTypes(array $kinds) {
    foreach ($this->cache as $node) {
      foreach ($kinds as $kind) {
        if ($node instanceof $kind) {
          yield $node;
        }
      }
    }
  }

}
