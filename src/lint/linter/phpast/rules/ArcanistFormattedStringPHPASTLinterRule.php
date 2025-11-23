<?php

/**
 * @phutil-external-symbol class PhpParser\ConstExprEvaluator
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Name
 */
final class ArcanistFormattedStringPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 54;

  private $printfFunctions = array();

  public function getLintName() {
    return pht('Formatted String');
  }

  public function getLinterConfigurationOptions() {
    return parent::getLinterConfigurationOptions() + array(
      'phpast.printf-functions' => array(
        'type' => 'optional map<string, int>',
        'help' => pht(
          '`%s`-style functions which take a format string and list of values '.
          'as arguments. The value for the mapping is the start index of the '.
          'function parameters (the index of the format string parameter).',
          'printf()'),
      ),
    );
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'phpast.printf-functions':
        $this->printfFunctions = $value;
        return;
      default:
        parent::setLinterConfigurationValue($key, $value);
        return;
    }
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    static $functions = array(
      // Core PHP
      'fprintf' => 1,
      'printf' => 0,
      'sprintf' => 0,
      'vfprintf' => 1,

      // Arcanist
      'csprintf' => 0,
      'execx' => 0,
      'exec_manual' => 0,
      'hgsprintf' => 0,
      'hsprintf' => 0,
      'jsprintf' => 0,
      'pht' => 0,
      'phutil_passthru' => 0,
      'qsprintf' => 1,
      'queryfx' => 1,
      'queryfx_all' => 1,
      'queryfx_one' => 1,
      'vcsprintf' => 0,
      'vqsprintf' => 1,
    );

    if (
      $node instanceof PhpParser\Node\Expr\FuncCall &&
      $node->name instanceof PhpParser\Node\Name) {

      $start = idx(
        $functions + $this->printfFunctions, $node->name->toLowerString());

      if ($start === null) {
        return;
      }

      $argc = count($node->args) - $start;

      if ($argc < 1) {
        $this->raiseLintAtNode(
          $node,
          pht('This function is expected to have a format string.'));
        return;
      }

      $format = $node->args[$start]->value;

      if (!$this->isConstantString($format)) {

        // TODO: When this parameter is not a constant string, the call may
        // be unsafe. We should make some attempt to warn about this for
        // "qsprintf()" and other security-sensitive functions.

        return;
      }

      $format_string = id(new PhpParser\ConstExprEvaluator())
        ->evaluateSilently($format);

      $argv = array($format_string) + array_fill(0, $argc, null);

      try {
        xsprintf(array(__CLASS__, 'processXsprintfCallback'), null, $argv);
      } catch (BadFunctionCallException $ex) {
        $this->raiseLintAtNode(
          $node,
          str_replace(
            'xsprintf',
            $node->name->toLowerString(),
            $ex->getMessage()));
      } catch (InvalidArgumentException $ex) {
        // Ignore.
      }
    }
  }

  public static function processXsprintfCallback(
    $userdata,
    &$pattern,
    &$pos,
    &$value,
    &$length) {

    if ($value !== null) {
      throw new Exception('Expected dummy value to be null');
    }

    // Turn format "%$pattern" with argument null into format "%s" with
    // argument "%$pattern". This ensures we always provide valid input for
    // sprintf to avoid getting a ValueError when using custom format
    // specifiers.
    $value = '%'.$pattern[$pos];
    $pattern[$pos] = 's';
  }

}
