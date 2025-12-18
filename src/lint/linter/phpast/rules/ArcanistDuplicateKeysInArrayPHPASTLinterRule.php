<?php

/**
 * Finds duplicate keys in array initializers, as in
 * `array(1 => 'anything', 1 => 'foo')`. Since the first entry is ignored, this
 * is almost certainly an error.
 *
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Identifier
 * @phutil-external-symbol class PhpParser\Node\Expr\Array_
 * @phutil-external-symbol class PhpParser\Node\Expr\ClassConstFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\ConstFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 * @phutil-external-symbol class PhpParser\Node\Scalar\Int_
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 */
final class ArcanistDuplicateKeysInArrayPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 22;

  public function getLintName() {
    return pht('Duplicate Keys in Array');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\Array_) {
      $nodes_by_key = array();
      $keys_warn = array();

      foreach ($node->items as $item) {
        if (!$item->key) {
          continue;
        }

        if (
          $item->key instanceof PhpParser\Node\Scalar\String_ ||
          $item->key instanceof PhpParser\Node\Scalar\Int_) {
          $key = 'scalar:'.$item->key->value;
        } else if (
          $item->key instanceof PhpParser\Node\Expr\Variable &&
          is_string($item->key->name)) {
          $key = $item->key->getType().':'.$item->key->name;
        } else if ($item->key instanceof PhpParser\Node\Expr\ConstFetch) {
          if ($item->key->name instanceof PhpParser\Node\Name) {
            $key = $item->key->getType().':'.$item->key->name->toLowerString();
          } else {
            $key = null;
          }
        } else if ($item->key instanceof PhpParser\Node\Expr\ClassConstFetch) {
          if (
            $item->key->class instanceof PhpParser\Node\Name &&
            $item->key->name instanceof PhpParser\Node\Identifier) {
            $key = $item->key->getType().':'.
              $item->key->class->toLowerString().'::'.
              $item->key->name->toLowerString();
          } else {
            $key = null;
          }
        } else {
          $key = null;
        }

        if ($key !== null) {
          if (isset($nodes_by_key[$key])) {
            $keys_warn[$key] = true;
          }
          $nodes_by_key[$key][] = $item->key;
        }
      }

      foreach ($keys_warn as $key => $_) {
        $node = array_pop($nodes_by_key[$key]);
        $message = $this->raiseLintAtNode(
          $node,
          pht(
            'Duplicate key in array initializer. '.
            'PHP will ignore all but the last entry.'));

        $locations = array();
        foreach ($nodes_by_key[$key] as $other_node) {
          $locations[] = $this->getOtherLocation(
            $other_node->getStartFilePos());
        }
        $message->setOtherLocations($locations);
      }
    }
  }

}
