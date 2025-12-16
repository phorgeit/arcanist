<?php

/**
 * Uses PHP-Parser to apply lint rules to PHP.
 *
 * @phutil-external-symbol class PhpParser\Error
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\NodeTraverser
 * @phutil-external-symbol class PhpParser\NodeVisitor\NameResolver
 * @phutil-external-symbol class PhpParser\NodeVisitor\ParentConnectingVisitor
 * @phutil-external-symbol class PhpParser\Token
 * @phutil-external-symbol class PhpParser\Parser
 * @phutil-external-symbol class PhpParser\NodeTraverser
 */
final class ArcanistPHPASTLinter extends ArcanistFutureLinter {

  /** @var array<string,MethodCallFuture> */
  private $futures = array();
  /** @var array<string,array{0:array<PhpParser\Node\Stmt>,1:array}|null> */
  private $asts = array();
  private $exceptions = array();
  private $refcount = array();
  /** @var array<ArcanistPHPASTLinterRule> */
  private $rules = array();
  private $lintNameMap;
  private $lintSeverityMap;

  public function __construct() {
    $this->setRules(ArcanistPHPASTLinterRule::loadAllRules());
  }

  public function canRun() {
    $library_version = PhutilPHPParserLibrary::getVersion();

    // Older versions of PHP-Parser do not provide easy access
    // to the token stream.
    // This means this linter is effectively PHP >= 7.4
    if ($library_version) {
      return version_compare($library_version, '5.0.0', '>=');
    } else {
      // If the library isn't available, we can predict which version will be
      // downloaded.
      // Only PHP 7.4 and newer download the new version of PHP-Parser.
      return PHP_VERSION_ID >= 70400;
    }
  }

  public function getCacheVersion() {
    $parts = array();

    $parts[] = count($this->rules);

    $version = PhutilPHPParserLibrary::getVersion();
    if ($version) {
      $parts[] = $version;
    }

    return implode('-', $parts);
  }

  /**
   * @param PhpParser\Token $token
   * @param int $code
   * @param string $desc
   * @param string|null $replace
   * @return ArcanistLintMessage
   */
  public function raiseLintAtToken(
    PhpParser\Token $token,
    $code,
    $desc,
    $replace = null) {
    return $this->raiseLintAtOffset(
      $token->pos,
      $code,
      $desc,
      $token->text,
      $replace);
  }

  /**
   * @param PhpParser\Node $node
   * @param int $code
   * @param string $desc
   * @param string|null $replace
   * @param array<PhpParser\Token>|null $token_stream
   * @return ArcanistLintMessage
   */
  public function raiseLintAtNode(
    PhpParser\Node $node,
    $code,
    $desc,
    $replace = null,
    $token_stream = null) {

    $original = null;

    if ($replace !== null) {
      if (!$token_stream) {
        throw new Exception(
          pht(
            'In order to provide replacements for nodes, '.
            'the original token stream is required.'));
      }
      $token_range = array_slice(
        $token_stream,
        $node->getStartTokenPos(),
        $node->getEndTokenPos() - $node->getStartTokenPos() + 1);
      $original = implode('', ppull($token_range, 'text'));
    }

    return $this->raiseLintAtOffset(
      $node->getStartFilePos(),
      $code,
      $desc,
      $original,
      $replace);
  }

  protected function buildFutures(array $paths) {
    return $this->getPHPASTLinter()->buildSharedFutures($paths);
  }

  protected function didResolveLinterFutures(array $futures) {
    $this->getPHPASTLinter()->releaseSharedFutures(array_keys($futures));
  }

  /* -(  Sharing Parse Trees  )---------------------------------------------- */

  /**
   * Get the linter object which is responsible for building parse trees.
   *
   * When the engine specifies that several PHPAST linters should execute,
   * we designate one of them as the one which will actually build parse trees.
   * The other linters share trees, so they don't have to recompute them.
   *
   * Roughly, the first linter to execute elects itself as the builder.
   * Subsequent linters request builds and retrieve results from it.
   *
   * @return self Responsible linter.
   * @task sharing
   */
  protected function getPHPASTLinter() {
    $resource_key = 'phpast.linter';

    // If we're the first linter to run, share ourselves. Otherwise, grab the
    // previously shared linter.

    $engine = $this->getEngine();
    $linter = $engine->getLinterResource($resource_key);
    if (!$linter) {
      $linter = $this;
      $engine->setLinterResource($resource_key, $linter);
    }

    if (!($linter instanceof self)) {
      throw new Exception(
        pht(
          'Expected resource "%s" to be an instance of "%s"!',
          $resource_key,
          self::class));
    }

    return $linter;
  }

  /**
   * Build futures on this linter, for use and to share with other linters.
   *
   * @param array<string> $paths List of paths to build futures for.
   * @return array<MethodCallFuture> List of futures.
   * @task sharing
   */
  protected function buildSharedFutures(array $paths) {
    $parser = PhutilPHPParserLibrary::getParser();

    foreach ($paths as $path) {
      if (!isset($this->futures[$path])) {
        $this->futures[$path] = new MethodCallFuture(
          $this,
          'parse',
          $this->getData($path),
          $parser);
        $this->refcount[$path] = 1;
      } else {
        $this->refcount[$path]++;
      }
    }
    return array_select_keys($this->futures, $paths);
  }

  /**
   * Parse callback for use in {@method: buildSharedFutures}.
   *
   * @internal This needs to be public to allow MethodCallFuture to access it.
   *
   * @param string $source_code
   * @param PhpParser\Parser $parser
   * @return array{0:PhpParserAst,1:array<PhpParser\Token>}
   */
  public function parse(string $source_code, PhpParser\Parser $parser) {
    $ast = $parser->parse($source_code);

    $traverser = new PhpParser\NodeTraverser();
    $traverser->addVisitor(new PhpParser\NodeVisitor\NameResolver());
    $traverser->addVisitor(new PhpParser\NodeVisitor\ParentConnectingVisitor());
    $ast = $traverser->traverse($ast);

    return array(new PhpParserAst($ast), $parser->getTokens());
  }

  /**
   * Release futures on this linter which are no longer in use elsewhere.
   *
   * @param array<string> $paths List of paths to release futures for.
   * @task sharing
   */
  protected function releaseSharedFutures(array $paths) {
    foreach ($paths as $path) {
      if (empty($this->refcount[$path])) {
        throw new Exception(
          pht(
            'Imbalanced calls to shared futures: each call to '.
            '%s for a path must be paired with a call to %s.',
            'buildSharedFutures()',
            'releaseSharedFutures()'));
      }

      $this->refcount[$path]--;

      if (!$this->refcount[$path]) {
        unset($this->refcount[$path]);
        unset($this->futures[$path]);
        unset($this->asts[$path]);
        unset($this->exceptions[$path]);
      }
    }
  }

  /**
   * Get a path's AST from the responsible linter.
   *
   * @param string $path Path to retrieve AST for.
   * @return array{0:PhpParserAst,1:array<PhpParser\Token>}|null
   *   tuple with the AST and token stream, or null if unparseable.
   * @task sharing
   */
  protected function getPHPASTForPath($path) {
    // If we aren't the linter responsible for actually building the parse
    // trees, go get the tree from that linter.
    if ($this->getPHPASTLinter() !== $this) {
      return $this->getPHPASTLinter()->getPHPASTForPath($path);
    }

    if (!array_key_exists($path, $this->asts)) {
      if (!array_key_exists($path, $this->futures)) {
        return null;
      }

      $this->asts[$path] = null;

      try {
        $this->asts[$path] = $this->futures[$path]->resolve();
      } catch (Throwable $ex) {
        $this->exceptions[$path] = $ex;
      }
    }

    return $this->asts[$path];
  }

  /**
   * Get a path's parse exception from the responsible linter.
   *
   * @param   string          $path Path to retrieve exception for.
   * @return  Exception|null  Parse exception, if available.
   * @task sharing
   */
  protected function getPHPASTExceptionForPath($path) {
    if ($this->getPHPASTLinter() !== $this) {
      return $this->getPHPASTLinter()->getPHPASTExceptionForPath($path);
    }

    return idx($this->exceptions, $path);
  }

  public function __clone() {
    $rules = $this->rules;

    $this->rules = array();
    foreach ($rules as $rule) {
      $this->rules[] = clone $rule;
    }
  }

  /**
   * Set the PHPAST linter rules which are enforced by this linter.
   *
   * This is primarily useful for unit tests in which it is desirable to test
   * linter rules in isolation. By default, all linter rules will be enabled.
   *
   * @param  array<ArcanistPHPASTLinterRule> $rules
   * @return $this
   */
  public function setRules(array $rules) {
    assert_instances_of($rules, ArcanistPHPASTLinterRule::class);
    $this->rules = $rules;
    return $this;
  }

  public function getInfoName() {
    return pht('PHP-Parser Lint');
  }

  public function getInfoDescription() {
    return pht(
      'Use PHP-Parser to enforce coding conventions on PHP source files.');
  }

  public function getAdditionalInformation() {
    $table = id(new PhutilConsoleTable())
      ->setBorders(true)
      ->addColumn('id',    array('title' => pht('ID')))
      ->addColumn('class', array('title' => pht('Class')))
      ->addColumn('name',  array('title' => pht('Name')));

    $rules = $this->rules;
    ksort($rules);

    foreach ($rules as $id => $rule) {
      $table->addRow(array(
        'id' => $id,
        'class' => get_class($rule),
        'name' => $rule->getLintName(),
      ));
    }

    return array(
      pht('Linter Rules') => $table->drawConsoleString(),
    );
  }

  public function getLinterName() {
    return 'PHPAST';
  }

  public function getLinterConfigurationName() {
    return 'phpast';
  }

  public function getLintNameMap() {
    if ($this->lintNameMap === null) {
      $this->lintNameMap = mpull(
        $this->rules,
        'getLintName',
        'getLintID');
    }

    return $this->lintNameMap;
  }

  public function getLintSeverityMap() {
    if ($this->lintSeverityMap === null) {
      $this->lintSeverityMap = mpull(
        $this->rules,
        'getLintSeverity',
        'getLintID');
    }

    return $this->lintSeverityMap;
  }

  public function getLinterConfigurationOptions() {
    return parent::getLinterConfigurationOptions() + array_mergev(
      mpull($this->rules, 'getLinterConfigurationOptions'));
  }

  public function setLinterConfigurationValue($key, $value) {
    $matched = false;

    foreach ($this->rules as $rule) {
      foreach ($rule->getLinterConfigurationOptions() as $k => $spec) {
        if ($k === $key) {
          $matched = true;
          $rule->setLinterConfigurationValue($key, $value);
        }
      }
    }

    if ($matched) {
      return;
    }

    parent::setLinterConfigurationValue($key, $value);
  }

  protected function resolveFuture($path, Future $future) {
    $tree = $this->getPHPASTForPath($path);

    if (!$tree) {
      $ex = $this->getPHPASTExceptionForPath($path);
      if ($ex instanceof PhpParser\Error) {
        if ($ex->hasColumnInfo()) {
          $columm = $ex->getStartColumn($this->getData($path));
        } else {
          $columm = 1;
        }

        $this->raiseLintAtLine(
          $ex->getStartLine(),
          $columm,
          ArcanistSyntaxErrorPHPASTLinterRule::ID,
          pht(
            'This file contains a syntax error: %s',
            $ex->getMessage()));
      } else if ($ex instanceof Exception) {
        $this->raiseLintAtPath(
          ArcanistUnableToParsePHPASTLinterRule::ID,
          $ex->getMessage());
      }
      return;
    }

    list($ast, $token_stream) = $tree;

    $node_rules = array();
    $tree_rules = array();

    foreach ($this->rules as $rule) {
      if ($this->isCodeEnabled($rule->getLintID())) {
        $rule->setLinter($this);
        if ($rule instanceof ArcanistPHPASTNodeLinterRule) {
          $node_rules[] = $rule;
        } else {
          $tree_rules[] = $rule;
        }
      }
    }

    $ast->iterate(function ($node) use ($node_rules, $token_stream) {
      foreach ($node_rules as $rule) {
        $rule->process($node, $token_stream);
      }
    });

    foreach ($tree_rules as $tree_rule) {
      $tree_rule->process($ast, $token_stream);
    }
  }

}
