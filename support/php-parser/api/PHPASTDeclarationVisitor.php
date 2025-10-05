<?php

/**
 * Finds class, trait, enum, interface and function declarations.
 *
 * Also records inheritance, and the types used by said inheritance.
 * @phutil-external-symbol class PhpParser\NodeVisitorAbstract
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 * @phutil-external-symbol class PhpParser\NodeFinder
 * @phutil-external-symbol class PhpParser\Node\Stmt\TraitUse
 * @phutil-external-symbol class PhpParser\Node\Stmt\Interface_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Trait_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Enum_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 */
final class PHPASTDeclarationVisitor extends PhpParser\NodeVisitorAbstract {

  private $extensions = array();

  private $implementations = array();

  private $declarations = array();

  private $traits = array();

  private $usedTypes = array();

  /**
   * @return array<string, array<string>>
   */
  public function getExtensions() {
    return $this->extensions;
  }

  /**
   * @return array<string, array<string>>
   */
  public function getImplementations() {
    return $this->implementations;
  }

  /**
   * @return array<array{type:string,name:string,symbol:PhpParser\Node}>
   */
  public function getDeclarations() {
    return $this->declarations;
  }

  /**
   * @return array<array{type:string,name:string,symbol:PhpParser\Node}>
   */
  public function getTraits() {
    return $this->traits;
  }

  public function getUsedTypes() {
    return $this->usedTypes;
  }

  public function enterNode(PhpParser\Node $node) {
    if ($node instanceof PhpParser\Node\Stmt\Class_) {
      // Anonymous classes do not have names.
      if (!$node->name) {
        return;
      }

      $type = 'class';
      $fqn = $node->namespacedName->toCodeString();

      if ($node->extends) {
        $this->extensions[$fqn][] = $node->extends->toCodeString();
        $this->usedTypes[] = array(
          'type' => 'class',
          'name' => $node->extends->toCodeString(),
          'symbol' => $node->extends,
        );
      }

      foreach ($node->implements as $interface_name) {
        $this->implementations[$fqn][] = $interface_name->toCodeString();
        $this->usedTypes[] = array(
          'type' => 'interface',
          'name' => $interface_name->toCodeString(),
          'symbol' => $interface_name,
        );
      }

      $node_finder = new PhpParser\NodeFinder();
      $trait_use = $node_finder->findInstanceOf(
        $node->stmts,
        PhpParser\Node\Stmt\TraitUse::class);

      foreach ($trait_use as $traits) {
        foreach ($traits->traits as $trait_name) {
          $this->traits[$fqn][] = $trait_name->toCodeString();
        }
      }
    } else if ($node instanceof PhpParser\Node\Stmt\Interface_) {
      $type = 'interface';
      $fqn = $node->namespacedName->toCodeString();

      foreach ($node->extends as $extension) {
        $this->extensions[$fqn][] = $extension->toCodeString();
        $this->usedTypes[] = array(
          'type' => 'interface',
          'name' => $extension->toCodeString(),
          'symbol' => $extension,
        );
      }
    } else if ($node instanceof PhpParser\Node\Stmt\Trait_) {
      $type = 'trait';
    } else if ($node instanceof PhpParser\Node\Stmt\Enum_) {
      $type = 'enum';
      $fqn = $node->namespacedName->toCodeString();

      foreach ($node->implements as $interface_name) {
        $this->implementations[$fqn][] = $interface_name->toCodeString();
        $this->usedTypes[] = array(
          'type' => 'interface',
          'name' => $interface_name->toCodeString(),
          'symbol' => $interface_name,
        );
      }
    } else if ($node instanceof PhpParser\Node\Stmt\Function_) {
      $type = 'function';
    } else {
      return null;
    }

    $this->declarations[] = array(
      'type' => $type,
      'name' => $node->namespacedName->toCodeString(),
      'symbol' => $node,
    );

    return null;
  }

}
