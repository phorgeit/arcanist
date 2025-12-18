<?php

/**
 * Find function calls.
 *
 * Finds:
 * - Explicit call
 * - String literal passed to call_user_func() or call_user_func_array()
 * - String literal in array literal in call_user_func()/call_user_func_array()
 * TODO: Possibly support these:
 * - String literal in ReflectionFunction().
 *
 * In the interest of efficiency, this also records the types used in
 * call_user_func, call_user_func_array and newv.
 *
 * @phutil-external-symbol class PhpParser\NodeVisitorAbstract
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Expr\Array_
 */
final class PHPASTCallVisitor extends PhpParser\NodeVisitorAbstract {

  private $usedFunctions = array();

  public function getUsedFunctions() {
    return $this->usedFunctions;
  }

  private $usedTypes = array();

  public function getUsedTypes() {
    return $this->usedTypes;
  }

  public function enterNode(PhpParser\Node $node) {
    if (!$node instanceof PhpParser\Node\Expr\FuncCall) {
      return null;
    }

    // We cannot analyze dynamic function calls.
    if (!($node->name instanceof PhpParser\Node\Name)) {
      return;
    }

    $name = $node->name->toCodeString();

    if ($name === '\call_user_func' || $name === '\call_user_func_array') {
      $this->parseCallUserFunc($node);
    } else if ($name === '\newv') {
      // TODO: phase out newv now that new PHP constructs are possible.
      $arg = idx($node->args, 0);

      // This is a bare newv() with no arguments; just ignore it.
      if (!$arg) {
        return;
      }

      // This is an unpacked argument (...$var, ...array()),
      // which we cannot analyze.
      if ($arg->unpack) {
        return;
      }

      if ($arg->value instanceof PhpParser\Node\Scalar\String_) {
        $this->usedTypes[] = array(
          'type' => 'class/enum',
          'name' => $arg->value->value,
          'symbol' => $arg->value,
        );
      }
    } else {
      $this->usedFunctions[] = array(
        'type' => 'function',
        'name' => $node->name->toCodeString(),
        'symbol' => $node,
      );
    }

    return null;
  }

  private function parseCallUserFunc(PhpParser\Node\Expr\FuncCall $node) {
   $arg = idx($node->args, 0);

    // This is a bare call_user_func() with no arguments; just ignore it.
    if (!$arg) {
      return;
    }

    // This is an unpacked argument (...$var, ...array()),
    // which we cannot analyze.
    if ($arg->unpack) {
      return;
    }

    $value = $arg->value;

    if ($value instanceof PhpParser\Node\Scalar\String_) {
      $pos = strpos($value->value, '::');

      if ($pos) {
        $this->usedTypes[] = array(
          'type' => 'class',
          'name' => substr($value->value, 0, $pos),
          'symbol' => $value,
        );
      } else {
        $this->usedFunctions[] = array(
          'type' => 'function',
          'name' => $value->value,
          'symbol' => $value,
        );
      }
    } else if ($value instanceof PhpParser\Node\Expr\Array_) {
      // Arrays need to contain exactly two elements to qualify as a callable.
      if (count($value->items) !== 2) {
        return;
      }

      list($object_or_class, $method) = $value->items;

      if (
        !$object_or_class ||
        $object_or_class->key ||
        $object_or_class->unpack) {

        return;
      }

      if ($object_or_class->value instanceof PhpParser\Node\Scalar\String_) {
        $this->usedTypes[] = array(
          'type' => 'class',
          'name' => $object_or_class->value->value,
          'symbol' => $value,
        );
      }
    }
  }

}
