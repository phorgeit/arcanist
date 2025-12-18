<?php

/**
 * Build maps of Arcanist libraries. Arcanist uses the library map to locate
 * and load classes and functions in the library.
 *
 * @task map      Mapping Arcanist Libraries
 * @task path     Path Management
 * @task symbol   Symbol Analysis and Caching
 * @task source   Source Management
 *
 * @phpstan-type LibraryMapArray array{class:array,function:array,xmap:array}
 */
final class PhutilLibraryMapBuilder extends Phobject {

  private $root;
  private $subprocessLimit = 8;
  private $alternativeParser = false;

  private $fileSymbolMap;
  private $librarySymbolMap;

  const LIBRARY_MAP_VERSION_KEY   = '__library_version__';
  const LIBRARY_MAP_VERSION       = 2;

  const SYMBOL_CACHE_VERSION_KEY  = '__symbol_cache_version__';
  const SYMBOL_CACHE_VERSION      = 11;


/* -(  Mapping Arcanist Libraries  )---------------------------------------- */

  /**
   * Create a new map builder for a library.
   *
   * @param string $root Path to the library root.
   *
   * @task map
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * Control subprocess parallelism limit. Use `--limit` to set this.
   *
   * @param  int   $limit Maximum number of subprocesses to run in parallel.
   * @return $this
   *
   * @task map
   */
  public function setSubprocessLimit($limit) {
    $this->subprocessLimit = $limit;
    return $this;
  }

  /**
   * Set if the alternative parser should be used.
   *
   * @param bool $alternative
   * @return $this
   *
   * @task map
   */
  public function setAlternativeParser($alternative) {
    $this->alternativeParser = $alternative;
    return $this;
  }

  /**
   * Get the map of symbols in this library, analyzing the library to build it
   * if necessary.
   *
   * @return LibraryMapArray Information about symbols in this library.
   *
   * @task map
   */
  public function buildMap() {
    if ($this->librarySymbolMap === null) {
      $this->analyzeLibrary();
    }
    return $this->librarySymbolMap;
  }


  /**
   * Get the map of files in this library, analyzing the library to build it
   * if necessary.
   *
   * Returns a map of file paths to information about symbols used and defined
   * in the file.
   *
   * @return array<string,LibraryMapArray> Information about files in this
   *                                       library.
   *
   * @task map
   */
  public function buildFileSymbolMap() {
    if ($this->fileSymbolMap === null) {
      $this->analyzeLibrary();
    }
    return $this->fileSymbolMap;
  }

  /**
   * Build and update the library map.
   *
   * @return void
   *
   * @task map
   */
  public function buildAndWriteMap() {
    $library_map = $this->buildMap();

    $this->writeLibraryMap($library_map);
  }


/* -(  Path Management  )---------------------------------------------------- */

  /**
   * Get the path to some file in the library.
   *
   * @param  string  $path (optional) A library-relative path. If omitted,
   *                 returns the library root path.
   * @return string  An absolute path.
   *
   * @task path
   */
  private function getPath($path = '') {
    return $this->root.'/'.$path;
  }

  /**
   * Get the path to the symbol cache file.
   *
   * @return string Absolute path to symbol cache.
   *
   * @task path
   */
  private function getPathForSymbolCache() {
    return $this->getPath('.phutil_module_cache');
  }

  /**
   * Get the path to the map file.
   *
   * @return string Absolute path to the library map.
   *
   * @task path
   */
  private function getPathForLibraryMap() {
    return $this->getPath('__phutil_library_map__.php');
  }

  /**
   * Get the path to the library init file.
   *
   * @return string Absolute path to the library init file
   *
   * @task path
   */
  private function getPathForLibraryInit() {
    return $this->getPath('__phutil_library_init__.php');
  }


/* -(  Symbol Analysis and Caching  )---------------------------------------- */

  /**
   * Load the library symbol cache, if it exists and is readable and valid.
   *
   * @return array Map of content hashes to cache of output from
   *               `extract-symbols.php`.
   *
   * @task symbol
   */
  private function loadSymbolCache() {
    $cache_file = $this->getPathForSymbolCache();

    try {
      $cache = Filesystem::readFile($cache_file);
    } catch (Exception $ex) {
      $cache = null;
    }

    $symbol_cache = array();
    if ($cache) {
      try {
        $symbol_cache = phutil_json_decode($cache);
      } catch (PhutilJSONParserException $ex) {
        $symbol_cache = array();
      }
    }

    $version = idx($symbol_cache, self::SYMBOL_CACHE_VERSION_KEY);
    if ($version != self::SYMBOL_CACHE_VERSION) {
      // Throw away caches from a different version of the library.
      $symbol_cache = array();
    }
    unset($symbol_cache[self::SYMBOL_CACHE_VERSION_KEY]);

    return $symbol_cache;
  }

  /**
   * Write a symbol map to disk cache.
   *
   * @param  array $symbol_map Symbol map of relative paths to symbols.
   * @param  array $source_map Source map (like @{method:loadSourceFileMap}).
   * @return void
   *
   * @task symbol
   */
  private function writeSymbolCache(array $symbol_map, array $source_map) {
    $cache_file = $this->getPathForSymbolCache();

    $cache = array(
      self::SYMBOL_CACHE_VERSION_KEY => self::SYMBOL_CACHE_VERSION,
    );

    foreach ($symbol_map as $file => $symbols) {
      $cache[$source_map[$file]] = $symbols;
    }

    $json = json_encode($cache);
    Filesystem::writeFile($cache_file, $json);
  }

  /**
   * Drop the symbol cache, forcing a clean rebuild.
   *
   * @return void
   *
   * @task symbol
   */
  public function dropSymbolCache() {
    Filesystem::remove($this->getPathForSymbolCache());
  }

  /**
   * Build a future which returns a `extract-symbols.php` analysis of a source
   * file.
   *
   * @param  string  $file Relative path to the source file to analyze.
   * @param  boolean $alternative_parser Use PHP-parser instead of XHPAST
   * @return Future  Analysis future.
   *
   * @task symbol
   */
  private function buildSymbolAnalysisFuture($file, bool $alternative_parser) {
    $absolute_file = $this->getPath($file);
    return self::newExtractSymbolsFuture(
      array(),
      array($absolute_file),
      $alternative_parser);
  }

  private static function newExtractSymbolsFuture(
    array $flags,
    array $paths,
    bool $alternative_parser) {

    $root = dirname(__FILE__).'/../../support/lib';

    if ($alternative_parser) {
      $bin = $root.'/extract-symbols-with-php-parser.php';
    } else {
      $bin = $root.'/extract-symbols.php';
    }

    return new ExecFuture(
      'php -f %R -- --ugly %Ls -- %Ls',
      $bin,
      $flags,
      $paths);
  }

  public static function newBuiltinMap(bool $alternative_parser) {
    $future = self::newExtractSymbolsFuture(
      array('--builtins'),
      array(),
      $alternative_parser);

    list($json) = $future->resolvex();

    return phutil_json_decode($json);
  }


/* -(  Source Management  )-------------------------------------------------- */

  /**
   * Build a map of all source files in a library to hashes of their content.
   * Returns an array like this:
   *
   *   array(
   *     'src/parser/ExampleParser.php' => '60b725f10c9c85c70d97880dfe8191b3',
   *     // ...
   *   );
   *
   * @return array Map of library-relative paths to content hashes.
   * @task source
   */
  private function loadSourceFileMap() {
    $root = $this->getPath();

    $init = $this->getPathForLibraryInit();
    if (!Filesystem::pathExists($init)) {
      throw new Exception(
        pht(
          "Provided path '%s' is not a %s library.",
          $root,
          'phutil'));
    }

    $files = id(new FileFinder($root))
      ->withType('f')
      ->withSuffix('php')
      ->excludePath('*/.*')
      ->setGenerateChecksums(true)
      ->find();

    $extensions_dir = 'extensions/';
    $extensions_len = strlen($extensions_dir);

    $map = array();
    foreach ($files as $file => $hash) {
      $file = Filesystem::readablePath($file, $root);
      $file = ltrim($file, '/');

      if (dirname($file) == '.') {
        // We don't permit normal source files at the root level, so just ignore
        // them; they're special library files.
        continue;
      }

      // Ignore files in the extensions/ directory.
      if (!strncmp($file, $extensions_dir, $extensions_len)) {
        continue;
      }

      // We include also filename in the hash to handle cases when the file is
      // moved without modifying its content.
      $map[$file] = md5($hash.$file);
    }

    return $map;
  }

  /**
   * Convert the symbol analysis of all the source files in the library into
   * a library map.
   *
   * @param   array $symbol_map Symbol analysis of all source files.
   * @return  array Library map.
   * @task source
   */
  private function buildLibraryMap(array $symbol_map) {
    $library_map = array(
      'class'     => array(),
      'function'  => array(),
      'xmap'      => array(),
    );

    $type_translation = array(
      'interface' => 'class',
      'trait' => 'class',
      'enum' => 'class',
    );

    // Detect duplicate symbols within the library.
    foreach ($symbol_map as $file => $info) {
      foreach ($info['have'] as $type => $symbols) {
        foreach ($symbols as $symbol => $declaration) {
          $lib_type = idx($type_translation, $type, $type);
          if (!empty($library_map[$lib_type][$symbol])) {
            $prior = $library_map[$lib_type][$symbol];
            throw new Exception(
              pht(
                "Definition of %s '%s' in file '%s' duplicates prior ".
                "definition in file '%s'. You can not declare the ".
                "same symbol twice.",
                $type,
                $symbol,
                $file,
                $prior));
          }
          $library_map[$lib_type][$symbol] = $file;
        }
      }
      $library_map['xmap'] += $info['xmap'];
    }

    // Simplify the common case (one parent) to make the file a little easier
    // to deal with.
    foreach ($library_map['xmap'] as $class => $extends) {
      if (count($extends) == 1) {
        $library_map['xmap'][$class] = reset($extends);
      }
    }

    // Sort the map so it is relatively stable across changes.
    foreach ($library_map as $key => $symbols) {
      ksort($symbols);
      $library_map[$key] = $symbols;
    }
    ksort($library_map);

    return $library_map;
  }

  /**
   * Write a finalized library map.
   *
   * @param  array $library_map Library map structure to write.
   * @return void
   *
   * @task source
   */
  private function writeLibraryMap(array $library_map) {
    $map_file = $this->getPathForLibraryMap();
    $version = self::LIBRARY_MAP_VERSION;

    $library_map = array(
      self::LIBRARY_MAP_VERSION_KEY => $version,
    ) + $library_map;

    $library_map = phutil_var_export($library_map);
    $at = '@';

    $source_file = <<<EOPHP
<?php

/**
 * This file is automatically generated. Use 'arc liberate' to rebuild it.
 *
 * {$at}generated
 * {$at}phutil-library-version {$version}
 */
phutil_register_library_map({$library_map});

EOPHP;

    Filesystem::writeFile($map_file, $source_file);
  }


  /**
   * Analyze the library, generating the file and symbol maps.
   *
   * @return void
   */
  private function analyzeLibrary() {
    // Identify all the ".php" source files in the library.
    $source_map = $this->loadSourceFileMap();

    // Load the symbol cache with existing parsed symbols. This allows us
    // to remap libraries quickly by analyzing only changed files.
    $symbol_cache = $this->loadSymbolCache();

    if ($this->alternativeParser) {
      // If the PHP-Parser library is not up-to-date, build it now. Otherwise,
      // `extract-symbols.php` will attempt to build the binary and will fail
      // miserably because it will be trying to download the same library
      // multiple times in parallel.
      if (!PhutilPHPParserLibrary::isAvailable()) {
        PhutilPHPParserLibrary::build();
      }
    } else {
      // If the XHPAST binary is not up-to-date, build it now. Otherwise,
      // `extract-symbols.php` will attempt to build the binary and will fail
      // miserably because it will be trying to build the same file multiple
      // times in parallel.
      if (!PhutilXHPASTBinary::isAvailable()) {
        PhutilXHPASTBinary::build();
      }
    }

    // Build out the symbol analysis for all the files in the library. For
    // each file, check if it's in cache. If we miss in the cache, do a fresh
    // analysis.
    $symbol_map = array();
    $futures = array();
    foreach ($source_map as $file => $hash) {
      if (!empty($symbol_cache[$hash])) {
        $symbol_map[$file] = $symbol_cache[$hash];
        continue;
      }
      $futures[$file] = $this->buildSymbolAnalysisFuture(
        $file,
        $this->alternativeParser);
    }

    // Run the analyzer on any files which need analysis.
    if ($futures) {
      $limit = $this->subprocessLimit;

      $progress = new PhutilConsoleProgressBar();
      $progress->setTotal(count($futures));

      $futures = id(new FutureIterator($futures))
        ->limit($limit);
      foreach ($futures as $file => $future) {
        $result = $future->resolveJSON();
        if (empty($result['error'])) {
          $symbol_map[$file] = $result;
        } else {
          $progress->done(false);
          throw new XHPASTSyntaxErrorException(
            $result['line'],
            $file.': '.$result['error']);
        }
        $progress->update(1);
      }
      $progress->done();
    }

    $this->fileSymbolMap = $symbol_map;

    if ($futures) {
      // We're done building/updating the cache, so write it out immediately.
      // Note that we've only retained entries for files we found, so this
      // implicitly cleans out old cache entries.
      $this->writeSymbolCache($symbol_map, $source_map);
    }

    // Our map is up to date, so either show it on stdout or write it to disk.
    $this->librarySymbolMap = $this->buildLibraryMap($symbol_map);
  }


}
