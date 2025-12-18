<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\NodeAbstract
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Coalesce
 * @phutil-external-symbol class PhpParser\Node\Expr\ClassConstFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\Closure
 * @phutil-external-symbol class PhpParser\Node\Expr\Empty_
 * @phutil-external-symbol class PhpParser\Node\Expr\Isset_
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 * @phutil-external-symbol class PhpParser\Node\Expr\StaticCall
 * @phutil-external-symbol class PhpParser\Node\Expr\StaticPropertyFetch
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Identifier
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassLike
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 */
final class PhpParserAst extends Phobject {

  private $nodes;

  private $cache = array();

  private $fullyCached = false;

  /**
   * @param array<PhpParser\Node> $nodes
   */
  public function __construct(array $nodes) {
    $this->nodes = $nodes;
  }

  /**
   * @param PhpParser\Node|array<PhpParser\Node> $nodes
   * @return self
   */
  public static function newPartialAst($nodes) {
    return new self((array)$nodes);
  }

  /**
   * Find top level nodes of a certain type. If a node of the same type
   * is nested within a found node, it will not be returned.
   *
   * @template TNode as PhpParser\Node
   *
   * @param class-string<TNode> $kind
   * @param array<class-string<PhpParser\Node>> $skip Nodes to skip traversing
   * @return array<TNode>
   */
  public function findTopLevelNodesOfKind(string $kind, array $skip = array()) {
    return $this->findNodesOfKindsNested(
      $this->nodes,
      array($kind),
      $skip,
      false);
  }

  /**
   * Find nodes of a certain type, descending as far as possible.
   *
   * @template TNode as PhpParser\Node
   *
   * @param class-string<TNode> $kind
   * @param array<class-string<PhpParser\Node>> $skip Nodes to skip traversing
   * @return array<TNode>
   */
  public function findNodesOfKind(
    string $kind,
    array $skip = array()) {

    if ($skip) {
      return $this->findNodesOfKindsNested(
        $this->nodes,
        array($kind),
        $skip,
        true);
    }

    if (!isset($this->cache[$kind])) {
      if ($this->fullyCached) {
        return array();
      }

      $this->cache[$kind] = $this->findNodesOfKindsNested(
        $this->nodes,
        array($kind),
        $skip,
        true);
    }

    return $this->cache[$kind];
  }

  /**
   * @param array<class-string<PhpParser\Node>> $skip
   * @return array<PhpParser\Node>
   */
  public function findNodesOfKinds(array $kinds, array $skip = array()) {
    if ($skip) {
      return $this->findNodesOfKindsNested(
        $this->nodes,
        $kinds,
        $skip,
        true);
    }

    $cached = array_intersect_key($this->cache, array_fuse($kinds));

    if ($this->fullyCached || count($cached) === count($kinds)) {
      return array_mergev(array_select_keys($this->cache, $kinds));
    }

    $nodes = $this->findNodesOfKindsNested(
      $this->nodes,
      $kinds,
      $skip,
      true);

    $cache = array();

    foreach ($nodes as $node) {
      foreach ($kinds as $kind) {
        if ($node instanceof $kind) {
          $cache[$kind][] = $node;
          break;
        }
      }
    }
    $this->cache += $cache;

    return array_mergev($cache);
  }

  /**
   * @param array<PhpParser\Node> $nodes
   * @param array<class-string<PhpParser\Node>> $kinds
   * @param array<class-string<PhpParser\Node>> $skip
   * @param bool $get_nested
   * @return array<PhpParser\Node>
   */
  private function findNodesOfKindsNested(
    array $nodes,
    array $kinds,
    array $skip,
    bool $get_nested) {

    $found = array();
    $stack = array_reverse($nodes);

    while ($stack) {
      $node = array_pop($stack);

      if (!($node instanceof PhpParser\Node)) {
        continue;
      }

      foreach ($skip as $skip_node) {
        if ($node instanceof $skip_node) {
          continue 2;
        }
      }

      foreach ($kinds as $kind) {
        if ($node instanceof $kind) {
          $found[] = $node;

          if ($get_nested) {
            break;
          } else {
            continue 2;
          }
        }
      }

      foreach ($node->getSubNodeNames() as $sub_node_name) {
        $sub_node = $node->{$sub_node_name};

        if ($sub_node instanceof PhpParser\Node) {
          $stack[] = $sub_node;
        } else if (is_array($sub_node)) {
          foreach ($sub_node as $sub_sub_node) {
            if ($sub_sub_node instanceof PhpParser\Node) {
              $stack[] = $sub_sub_node;
            }
          }
        }
      }
    }

    return $found;
  }

  /**
   * @return Generator<PhpParser\Node, bool>
   */
  public function findStaticAccess() {
    $stack = array_reverse($this->nodes);

    $closure_depth = 0;
    $in_closure = false;

    while ($stack) {
      if ($closure_depth > count($stack)) {
        $in_closure = false;
      }

      $node = array_pop($stack);

      if (!($node instanceof PhpParser\Node)) {
        continue;
      }

      if ($node instanceof PhpParser\Node\Expr\Closure) {
        $closure_depth = count($stack);
        $in_closure = true;
      } else if (
        $node instanceof PhpParser\Node\Expr\StaticCall ||
        $node instanceof PhpParser\Node\Expr\StaticPropertyFetch ||
        $node instanceof PhpParser\Node\Expr\ClassConstFetch) {
        if (
          $node->class instanceof PhpParser\Node\Name ||
          $node->class instanceof PhpParser\Node\Identifier) {
          yield $node => $in_closure;
        }
      }

      foreach ($node->getSubNodeNames() as $sub_node_name) {
        $sub_node = $node->{$sub_node_name};

        if ($sub_node instanceof PhpParser\Node) {
          $stack[] = $sub_node;
        } else if (is_array($sub_node)) {
          foreach ($sub_node as $sub_sub_node) {
            if ($sub_sub_node instanceof PhpParser\Node) {
              $stack[] = $sub_sub_node;
            }
          }
        }
      }
    }
  }

  /**
   * Find all variables in this scope, excluding those defined in closures,
   * nested functions or anonymous classes, and excluding variables that
   * may be undefined, such as in isset, empty or null coalescing
   * expressions.
   *
   * Variable variables are unnested, only the most right hand side will be
   * returned.
   * For $$var (and $$$var, and so on), it only returns $var.
   *
   * @return array<PhpParser\Node\Expr\Variable>
   */
  public function findVariablesInScope() {
    $variables = array();

    $stack = array_reverse($this->nodes);

    while ($stack) {
      $node = array_pop($stack);

      if ($node instanceof PhpParser\Node\Expr\Variable) {
        if (is_string($node->name)) {
          $variables[] = $node;
        }
      } else if (
        !($node instanceof PhpParser\Node) ||
        $node instanceof PhpParser\Node\Expr\Closure ||
        $node instanceof PhpParser\Node\Stmt\Function_ ||
        $node instanceof PhpParser\Node\Stmt\ClassLike ||
        $node instanceof PhpParser\Node\Expr\Empty_ ||
        $node instanceof PhpParser\Node\Expr\Isset_) {
       continue;
      } else if ($node instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
        // The left-hand side of the coalesce operator behaves like isset.
        // Those variables do not count.
        $node = $node->right;
      }

      foreach ($node->getSubNodeNames() as $sub_node_name) {
        $sub_node = $node->{$sub_node_name};

        if ($sub_node instanceof PhpParser\Node) {
          $stack[] = $sub_node;
        } else if (is_array($sub_node)) {
          foreach ($sub_node as $sub_sub_node) {
            if ($sub_sub_node instanceof PhpParser\Node) {
              $stack[] = $sub_sub_node;
            }
          }
        }
      }
    }

    return $variables;
  }

  /**
   * Iterate all nodes in this AST.
   * Fills the cache at the same time.
   *
   * @param callable $on_node
   */
  public function iterate(callable $on_node) {
    $this->cache = array();
    $stack = array_reverse($this->nodes);

    while ($stack) {
      $node = array_pop($stack);

      if (!($node instanceof PhpParser\Node)) {
        continue;
      }

      $parents = array_merge(
        class_parents($node, false),
        class_implements($node, false));
      $parents[] = get_class($node);

      foreach ($parents as $parent) {
        if (
          $parent === JsonSerializable::class ||
          $parent === PhpParser\Node::class ||
          $parent === PhpParser\NodeAbstract::class) {
          continue;
        }

        $this->cache[$parent][] = $node;
      }

      $on_node($node);

      foreach ($node->getSubNodeNames() as $sub_node_name) {
        $sub_node = $node->{$sub_node_name};

        if ($sub_node instanceof PhpParser\Node) {
          $stack[] = $sub_node;
        } else if (is_array($sub_node)) {
          foreach ($sub_node as $sub_sub_node) {
            if ($sub_sub_node instanceof PhpParser\Node) {
              $stack[] = $sub_sub_node;
            }
          }
        }
      }
    }

    // Mark the cacheas complete, as we have all the nodes identified.
    // Iterating the tree for nodes not in the cache is pointless.
    $this->fullyCached = true;
  }

}
