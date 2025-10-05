<?php

/**
 * Collects types used in:
 * - Parameters
 * - Return types
 * - Properties
 * - catch clauses
 * - instanceof expressions
 * - Static constant, property and method access
 * - Instantiations
 * TODO: Possibly support these:
 * - String literal in ReflectionClass()
 *
 * @phutil-external-symbol class PhpParser\NodeVisitorAbstract
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\New_
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Stmt\Catch_
 * @phutil-external-symbol class PhpParser\Node\Expr\Instanceof_
 * @phutil-external-symbol class PhpParser\Node\Expr\ClassConstFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\StaticPropertyFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\StaticCall
 * @phutil-external-symbol class PhpParser\Node\Stmt\Property
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassConst
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 * @phutil-external-symbol class PhpParser\Node\NullableType
 * @phutil-external-symbol class PhpParser\Node\IntersectionType
 * @phutil-external-symbol class PhpParser\Node\UnionType
 */
final class PHPASTTypesVisitor extends PhpParser\NodeVisitorAbstract {

  private $usedTypes = array();

  public function getUsedTypes() {
    return $this->usedTypes;
  }

  public function enterNode(PhpParser\Node $node) {
    if ($node instanceof PhpParser\Node\Expr\New_) {
      // We cannot analyze anonymous classes or dynamic calls.
      if (!($node->class instanceof PhpParser\Node\Name)) {
        return null;
      }

      $this->usedTypes[] = array(
        'type'   => 'class',
        'name'   => $node->class->toCodeString(),
        'symbol' => $node,
      );
    } else if ($node instanceof PhpParser\Node\Stmt\Catch_) {
      foreach ($node->types as $type) {
        $this->usedTypes[] = array(
          'type'   => 'class/interface/enum',
          'name'   => $type->toCodeString(),
          'symbol' => $node,
        );
      }
    } else if (
      $node instanceof PhpParser\Node\Expr\Instanceof_ ||
      $node instanceof PhpParser\Node\Expr\ClassConstFetch ||
      $node instanceof PhpParser\Node\Expr\StaticPropertyFetch ||
      $node instanceof PhpParser\Node\Expr\StaticCall) {

      // We cannot analyze dynamic calls.
      if (!($node->class instanceof PhpParser\Node\Name)) {
        return null;
      }

      $this->usedTypes[] = array(
        'type'   => 'class/interface/enum',
        'name'   => $node->class->toCodeString(),
        'symbol' => $node,
      );
    } else if (
      $node instanceof PhpParser\Node\Stmt\Property ||
      $node instanceof PhpParser\Node\Stmt\ClassConst) {

      if ($node->type) {
        $this->parseTypeDeclaration($node->type);
      }
    } else if ($node instanceof PhpParser\Node\FunctionLike) {
      $return_type = $node->getReturnType();
      if ($return_type) {
        $this->parseTypeDeclaration($return_type);
      }

      foreach ($node->getParams() as $param) {
        if ($param->type) {
          $this->parseTypeDeclaration($param->type);
        }
      }
    }

    return null;
  }

  /**
   * @param PhpParser\Node\Identifier|PhpParser\Node\Name|PhpParser\Node\ComplexType $node
   */
  private function parseTypeDeclaration($node) {
    if ($node instanceof PhpParser\Node\NullableType) {
      $this->parseTypeDeclaration($node->type);
    } else if (
      $node instanceof PhpParser\Node\IntersectionType ||
      $node instanceof PhpParser\Node\UnionType) {

      foreach ($node->types as $type) {
        $this->parseTypeDeclaration($type);
      }
    } else if ($node instanceof PhpParser\Node\Name) {
      $this->usedTypes[] = array(
        'type'   => 'class/interface/enum',
        'name'   => $node->toCodeString(),
        'symbol' => $node,
      );
    }
    // PhpParser\Node\Identifier is omitted because it refers to non-types.
  }

}
