<?php

/**
 * @phutil-external-symbol class PhpParser\NodeVisitorAbstract
 * @phutil-external-symbol class PhpParser\Node
 */
final class PHPASTExternalSymbolVisitor extends PhpParser\NodeVisitorAbstract {

  /**
   * @var array<string,array<string, true>>
   */
  private $externalSymbols = array();

  private $docBlockParser;

  private $nameContext;

  public function __construct(
    PhpParser\NameContext $name_context) {

    $this->docBlockParser = new PhutilDocblockParser();
    $this->nameContext = $name_context;
  }

  public function getExternalSymbols() {
    return $this->externalSymbols;
  }

  public function enterNode(PhpParser\Node $node) {
    if ($node->getDocComment()) {
      list($block, $special) = $this->docBlockParser
        ->parse($node->getDocComment()->getText());

      $ext_list = idx($special, 'phutil-external-symbol');
      $ext_list = (array)$ext_list;
      $ext_list = array_filter($ext_list);

      foreach ($ext_list as $ext_ref) {
        $matches = null;
        if (preg_match('/^\s*(\S+)\s+(\S+)/', $ext_ref, $matches)) {
          list(, $type, $name) = $matches;
          $name = $this->resolveName($name, $type);

          $this->externalSymbols[$type][$name] = true;
        }
      }
    }

    return null;
  }

  private function resolveName(string $name, string $type) {
    if ($type === 'function') {
      $type = PhpParser\Node\Stmt\Use_::TYPE_FUNCTION;
    } else if ($type === 'class') {
      $type = PhpParser\Node\Stmt\Use_::TYPE_NORMAL;
    } else if ($type === 'constant') {
      $type = PhpParser\Node\Stmt\Use_::TYPE_CONSTANT;
    }

    $resolved_name = $this->nameContext->getResolvedName(
      new PhpParser\Node\Name($name),
      $type);

    if ($resolved_name) {
      // When resolving namespaces, PHP-Parser appends a leading namespace
      // separator to every type name. All other code assumes no such thing.
      return ltrim($resolved_name->toCodeString(), '\\');
    }

    return $name;
  }

}
