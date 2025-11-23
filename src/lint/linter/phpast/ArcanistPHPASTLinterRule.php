<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Coalesce
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Concat
 * @phutil-external-symbol class PhpParser\Node\Expr\ClassConstFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\Closure
 * @phutil-external-symbol class PhpParser\Node\Expr\Empty_
 * @phutil-external-symbol class PhpParser\Node\Expr\Isset_
 * @phutil-external-symbol class PhpParser\Node\Expr\StaticCall
 * @phutil-external-symbol class PhpParser\Node\Expr\StaticPropertyFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassLike
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Identifier
 * @phutil-external-symbol class PhpParser\Token
 */
abstract class ArcanistPHPASTLinterRule extends Phobject {

  /** @var ArcanistPHPASTLinter */
  private $linter;

  private $lintID;

  protected $version;

  protected $windowsVersion;

  final public static function loadAllRules() {
    return id(new PhutilClassMapQuery())
      ->setAncestorClass(self::class)
      ->setUniqueMethod('getLintID')
      ->execute();
  }

  final public function getLintID() {
    if ($this->lintID === null) {
      $class = new ReflectionClass($this);

      $const = $class->getConstant('ID');
      if ($const === false) {
        throw new Exception(
          pht(
            '`%s` class `%s` must define an ID constant.',
            self::class,
            get_class($this)));
      }

      if (!is_int($const)) {
        throw new Exception(
          pht(
            '`%s` class `%s` has an invalid ID constant. '.
            'ID must be an integer.',
            self::class,
            get_class($this)));
      }

      $this->lintID = $const;
    }

    return $this->lintID;
  }

  abstract public function getLintName();

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ERROR;
  }

  public function getLinterConfigurationOptions() {
    return array(
      'phpast.php-version' => array(
        'type' => 'optional string',
        'help' => pht('PHP version to target.'),
      ),
      'phpast.php-version.windows' => array(
        'type' => 'optional string',
        'help' => pht('PHP version to target on Windows.'),
      ),
    );
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'phpast.php-version':
        $this->version = $value;
        return;

      case 'phpast.php-version.windows':
        $this->windowsVersion = $value;
        return;
    }
  }

  final public function setLinter(ArcanistPHPASTLinter $linter) {
    $this->linter = $linter;
    return $this;
  }

  /* -(  Proxied Methods  )-------------------------------------------------- */

  final public function getActivePath() {
    return $this->linter->getActivePath();
  }

  final public function getOtherLocation($offset, $path = null) {
    return $this->linter->getOtherLocation($offset, $path);
  }

  final protected function raiseLintAtPath($desc) {
    return $this->linter->raiseLintAtPath($this->getLintID(), $desc);
  }

  final public function raiseLintAtOffset(
    $offset,
    $description,
    $original = null,
    $replacement = null) {

    $this->linter->raiseLintAtOffset(
      $offset,
      $this->getLintID(),
      $description,
      $original,
      $replacement);
  }

  /**
   * @param PhpParser\Token $token
   * @param string $description
   * @param string|null $replace
   * @return ArcanistLintMessage
   * @throws Exception
   */
  final protected function raiseLintAtToken(
    PhpParser\Token $token,
    $description,
    $replace = null) {

    return $this->linter->raiseLintAtToken(
      $token,
      $this->getLintID(),
      $description,
      $replace);
  }

  /**
   * @param PhpParser\Node $node
   * @param string $description
   * @param string|null $replace
   * @param array<PhpParser\Token>|null $token_stream
   * @return ArcanistLintMessage
   * @throws Exception
   */
  final protected function raiseLintAtNode(
    PhpParser\Node $node,
    $description,
    $replace = null,
    $token_stream = null) {

    return $this->linter->raiseLintAtNode(
      $node,
      $this->getLintID(),
      $description,
      $replace,
      $token_stream);
  }

  /**
   * Get PHP superglobals.
   *
   * PHP-Parser processes these without the dollar sign, so they have been
   * omitted here as well.
   *
   * @return array<string> List of superglobals.
   */
  protected function getSuperGlobalNames() {
    return array(
      'GLOBALS',
      '_SERVER',
      '_GET',
      '_POST',
      '_FILES',
      '_COOKIE',
      '_SESSION',
      '_REQUEST',
      '_ENV',
    );
  }

  /**
   * @param PhpParser\Node $node
   * @param array<PhpParser\Token> $token_stream
   * @return string|null
   */
  protected function getIndentation(
    PhpParser\Node $node,
    array $token_stream) {
    $offset = $node->getStartTokenPos() - 1;
    $token = $token_stream[$offset];

    while (
      $offset >= 0 &&
      (!$token->isIgnorable() || strpos($token->text, "\n") === false)) {

      $offset--;
      $token = $token_stream[$offset];
    }

    if ($offset === 0) {
      return null;
    }

    return preg_replace("/^.*\n/s", '', $token->text);
  }

  protected function getString(PhpParser\Node $node, array $token_stream) {
    $token_range = array_slice(
      $token_stream,
      $node->getStartTokenPos(),
      $node->getEndTokenPos() - $node->getStartTokenPos() + 1);

    return implode('', ppull($token_range, 'text'));
  }

  /**
   * @param PhpParser\Node $node
   * @param array<PhpParser\Token> $token_stream
   * @return string
   */
  protected function getSemanticString(
    PhpParser\Node $node,
    array $token_stream) {

    $s = '';

    for ($i = $node->getStartTokenPos(); $i <= $node->getEndTokenPos(); $i++) {
      $token = $token_stream[$i];

      if ($token->isIgnorable()) {
        continue;
      }

      $s .= $token->text;
    }

    return $s;
  }

  protected function isConstantString(PhpParser\Node $node) {
    if ($node instanceof PhpParser\Node\Scalar\String_) {
      return true;
    } else if ($node instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
      return $this->isConstantString($node->left) &&
        $this->isConstantString($node->right);
    }

    return false;
  }

  /**
   * @param PhpParser\Node $node
   * @param array<PhpParser\Token> $token_stream
   * @return array<PhpParser\Token>
   */
  protected function getNonsemanticTokensBeforeNode(
    PhpParser\Node $node,
    array $token_stream) {

    $result = array();
    $i = $node->getStartTokenPos() - 1;

    while ($i >= 0 && $token_stream[$i]->isIgnorable()) {
      $result[$i] = $token_stream[$i];
      $i--;
    }

    return array_reverse($result);
  }

  /**
   * @param PhpParser\Node $node
   * @param array<PhpParser\Token> $token_stream
   * @return array<PhpParser\Token>
   */
  protected function getNonsemanticTokensAfterNode(
    PhpParser\Node $node,
    array $token_stream) {

    $max_tokens = count($token_stream);
    $result = array();
    $i = $node->getEndTokenPos() + 1;

    while ($i < $max_tokens && $token_stream[$i]->isIgnorable()) {
      $result[$i] = $token_stream[$i];
      $i++;
    }

    return $result;
  }

  /**
   * @param int $token_index
   * @param array<PhpParser\Token> $token_stream
   * @return array<PhpParser\Token>
   */
  protected function getNonsemanticTokensBefore(
    int $token_index,
    array $token_stream) {

    $tokens = array();

    do {
      $token_index--;

      if ($token_stream[$token_index]->isIgnorable()) {
        $tokens[$token_index] = $token_stream[$token_index];
      } else {
        break;
      }
    } while ($token_index >= 0);

    return array_reverse($tokens);
  }

  /**
   * @param int $token_index
   * @param array<PhpParser\Token> $token_stream
   * @return array<PhpParser\Token>
   */
  protected function getNonsemanticTokensAfter(
    int $token_index,
    array $token_stream) {

    $max_tokens = count($token_stream);
    $tokens = array();

    for ($i = $token_index + 1; $i < $max_tokens; $i++) {
      if ($token_stream[$i]->isIgnorable()) {
        $tokens[] = $token_stream[$i];
      } else {
        break;
      }
    }

    return $tokens;
  }

}
