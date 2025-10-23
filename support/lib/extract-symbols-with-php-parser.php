#!/usr/bin/env php
<?php

// We have to do this first before we load any symbols, because we define the
// built-in symbol list through introspection.
$builtins = phutil_symbols_get_builtins();

$root = dirname(__DIR__, 2);
require_once $root.'/support/init/init-script.php';

$args = new PhutilArgumentParser($argv);
$args->setTagline(pht('identify symbols in PHP source files'));
$args->setSynopsis(<<<EOHELP
    **extract-symbols-with-php-parser.php** [__options__] __path.php__
        Identify the symbols (classes, interfaces, traits, enums and functions)
        in PHP source files. Symbols are divided into "have" symbols
        (symbols the file declares) and "need" symbols (symbols the file
        depends on). For example, class declarations are "have" symbols,
        while object instantiations with "new X()" are "need" symbols.

        Dependencies on builtins and symbols marked '@phutil-external-symbol'
        in docblocks are omitted without __--all__.

        Symbols are reported in JSON on stdout.

        This script is used internally by Arcanist to build maps of library
        symbols.

EOHELP
);

$args->parseStandardArguments();
$args->parse(
  array(
    array(
      'name'     => 'all',
      'help'     => pht(
        'Emit all symbols, including built-ins and declared externals.'),
    ),
    array(
      'name'     => 'builtins',
      'help'     => pht('Emit builtin symbols.'),
    ),
    array(
      'name'     => 'ugly',
      'help'     => pht('Do not prettify JSON output.'),
    ),
    array(
      'name'     => 'path',
      'wildcard' => true,
      'help'     => pht('PHP Source file to analyze.'),
    ),
  ));

$paths = $args->getArg('path');
$show_all = $args->getArg('all');
$show_builtins = $args->getArg('builtins');

if (extension_loaded('xdebug') && !$show_builtins) {
  // PHP-parser warns about using xdebug.
  // In practice, the speed difference is not significant,
  // but the default nesting level is too low.
  ini_set('xdebug.max_nesting_level', 3000);
}

if ($show_builtins) {
  if ($show_all) {
    throw new PhutilArgumentUsageException(
      pht(
        'Flags "--all" and "--builtins" are not compatible.'));
  }

  if ($paths) {
    throw new PhutilArgumentUsageException(
      pht(
        'Flag "--builtins" may not be used with a path.'));
  }

  $declared_symbols = array();
  foreach ($builtins as $type => $builtin_symbols) {
    foreach ($builtin_symbols as $builtin_symbol => $ignored) {
      $declared_symbols[$type][$builtin_symbol] = null;
    }
  }

  $result = array(
    'have' => $declared_symbols,
    'need' => array(),
    'xmap' => array(),
  );

  if ($args->getArg('ugly')) {
    echo json_encode($result);
  } else {
    $json = new PhutilJSON();
    echo $json->encodeFormatted($result);
  }

  exit(0);
}

$paths = array_map('Filesystem::resolvePath', $paths);
$parser = PhutilPHPParserLibrary::getParser();

// Load these classes now, as getParser will have downloaded PHP-Parser and
// registered its autoloader, or thrown an exception. The base class these
// visitors extends isn't available otherwise.
require_once $root.'/support/php-parser/api/PHPASTDocBlockVisitor.php';
require_once $root.'/support/php-parser/api/PHPASTCallVisitor.php';
require_once $root.'/support/php-parser/api/PHPASTDeclarationVisitor.php';
require_once $root.'/support/php-parser/api/PHPASTTypesVisitor.php';

$results = array();

foreach ($paths as $path) {
  $source_code = Filesystem::readFile($path);

  try {
    $ast = $parser->parse($source_code);
    $result = phutil_parse_file($ast, $builtins, $show_all);
  } catch (PhpParser\Error $ex) {
    $result = array(
      'error' => $ex->getMessage(),
      'line'  => $ex->getStartLine(),
      'file'  => $path,
    );
  }

  $results[$path] = $result;
}

if (count($paths) === 1) {
  $results = head($results);
}

if ($args->getArg('ugly')) {
  echo json_encode($results);
} else {
  $json = new PhutilJSON();
  echo $json->encodeFormatted($results);
}

/**
 * @param PhpParser\Node\Stmt[] $ast
 * @param array $builtins
 * @param bool $show_all
 * @return array
 */
function phutil_parse_file(
  array $ast,
  array $builtins,
  bool $show_all): array {

  $doc_block_visitor = new PHPASTDocBlockVisitor();
  $call_visitor = new PHPASTCallVisitor();
  $declaration_visitor = new PHPASTDeclarationVisitor();
  $types_visitor = new PHPASTTypesVisitor();

  $traverser = new PhpParser\NodeTraverser();
  $traverser->addVisitor(new PhpParser\NodeVisitor\NameResolver());
  $ast = $traverser->traverse($ast);

  $traverser = new PhpParser\NodeTraverser();
  $traverser->addVisitor($doc_block_visitor);
  $traverser->addVisitor($call_visitor);
  $traverser->addVisitor($declaration_visitor);
  $traverser->addVisitor($types_visitor);
  $traverser->traverse($ast);

  $externals = array();
  $doc_parser = new PhutilDocblockParser();

  foreach ($doc_block_visitor->getDocBlocks() as $doc_block) {
    list($block, $special) = $doc_parser->parse($doc_block->getText());

    $ext_list = idx($special, 'phutil-external-symbol');
    $ext_list = (array)$ext_list;
    $ext_list = array_filter($ext_list);

    foreach ($ext_list as $ext_ref) {
      $matches = null;
      if (preg_match('/^\s*(\S+)\s+(\S+)/', $ext_ref, $matches)) {
        $externals[$matches[1]][$matches[2]] = true;
      }
    }
  }

  $declared_symbols = array();
  foreach ($declaration_visitor->getDeclarations() as $spec) {
    // When resolving namespaces, PHP-Parser appends a leading namespace
    // separator to every type name. All other code assumes no such thing.
    $name = ltrim($spec['name'], '\\');
    $type = $spec['type'];
    $declared_symbols[$type][$name] = $spec['symbol']->getStartFilePos();
  }

  $need = array_merge(
    $types_visitor->getUsedTypes(),
    $call_visitor->getUsedFunctions(),
    $call_visitor->getUsedTypes(),
    $declaration_visitor->getUsedTypes());

  $required_symbols = array();
  foreach ($need as $spec) {
    // When resolving namespaces, PHP-Parser appends a leading namespace
    // separator to every type name. All other code assumes no such thing.
    $name = ltrim($spec['name'], '\\');
    $type = $spec['type'];

    foreach (explode('/', $type) as $libtype) {
      if (!$show_all) {
        if (
          !empty($externals[$libtype][$name])) {
          // Ignore symbols declared as externals.
          continue 2;
        }
        if (
          !empty($builtins[$libtype][$name])) {
          // Ignore symbols declared as builtins.
          continue 2;
        }
      }
      if (!empty($declared_symbols[$libtype][$name])) {
        // We declare this symbol, so don't treat it as a requirement.
        continue 2;
      }
    }
    if (!empty($required_symbols[$type][$name])) {
      // Report only the first use of a symbol, since reporting all of them
      // isn't terribly informative.
      continue;
    }
    $required_symbols[$type][$name] = $spec['symbol']->getStartFilePos();
  }

  // When resolving namespaces, PHP-Parser appends a leading namespace
  // separator to every type name. All other code assumes no such thing.
  $xmap = array();
  foreach ($declaration_visitor->getExtensions() as $user => $types) {
    foreach ($types as $type) {
      $xmap[$user][] = ltrim($type, '\\');
    }
  }

  foreach ($declaration_visitor->getImplementations() as $user => $types) {
    foreach ($types as $type) {
      $xmap[$user][] = ltrim($type, '\\');
    }
  }

  foreach ($declaration_visitor->getTraits() as $user => $types) {
    foreach ($types as $type) {
      $xmap[$user][] = ltrim($type, '\\');
    }
  }

  return array(
    'have'  => $declared_symbols,
    'need'  => $required_symbols,
    'xmap'  => $xmap,
  );
}

function phutil_symbols_get_builtins() {
  $builtin = array(
    'classes'    => get_declared_classes(),
    'interfaces' => get_declared_interfaces(),
    'traits'     => get_declared_traits(),
    'functions'  => get_defined_functions()['internal'],
  );

  $file_content = file_get_contents(
      __DIR__.'/../../resources/php/symbol-information.json');
  if ($file_content) {
    $compat = json_decode($file_content, true);

    // Traits are not yet listed in symbol-information.json.
    // TODO: Regenerate symbol-information.json
    if (!isset($compat['traits'])) {
      $compat['traits'] = array();
    }
  } else {
    throw new Exception(pht('Symbol Information file does not exist!'));
  }

  $types = array('functions', 'classes', 'interfaces', 'traits');
  foreach ($types as $type) {
    // Developers may not have every extension that a library potentially uses
    // installed. We supplement the list of declared functions and classes with
    // a list of known extension functions to avoid raising false positives just
    // because you don't have pcntl, etc.
    $extensions = array_keys($compat[$type]);
    $builtin[$type] = array_merge($builtin[$type], $extensions);
  }

  return array(
    'class'     => array_fill_keys($builtin['classes'], true) + array(
      'static'  => true,
      'parent'  => true,
      'self'    => true,

      // PHP7 types.
      'bool' => true,
      'float' => true,
      'int' => true,
      'string' => true,
      'iterable' => true,
      'object' => true,
      'void' => true,
      // PHP8 types.
      'mixed' => true,
      'true' => true,
      'false' => true,
      'null' => true,
      'never' => true,
    ),
    'function'  => array(
      'empty' => true,
      'isset' => true,
      'die'   => true,
    ) + array_fill_keys($builtin['functions'], true),
    'interface' => array_fill_keys($builtin['interfaces'], true),
    'trait' => array_fill_keys($builtin['traits'], true),
  );
}
