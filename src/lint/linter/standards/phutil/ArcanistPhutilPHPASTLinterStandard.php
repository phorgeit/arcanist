<?php

final class ArcanistPhutilPHPASTLinterStandard
  extends ArcanistLinterStandard {

  public function getKey() {
    return 'phutil.phpast';
  }

  public function getName() {
    return pht('Phutil PHPAST');
  }

  public function getDescription() {
    return pht('PHP Coding Standards for Phutil libraries.');
  }

  public function supportsLinter(ArcanistLinter $linter) {
    return $linter instanceof ArcanistPHPASTLinter;
  }

  public function getLinterConfiguration() {
    return array(
      'phpast.blacklisted.function' => array(
        'eval' => pht(
          'The `%s` function should be avoided. It is potentially unsafe '.
          'and makes debugging more difficult.',
          'eval'),
       ),
      'phpast.php-version' => '7.2.25',
      'phpast.php-version.windows' => '7.2.25',
      'phpast.dynamic-string.classes' => array(
        'ExecFuture' => 0,
      ),
      'phpast.dynamic-string.functions' => array(
        'pht' => 0,

        'hsprintf' => 0,
        'jsprintf' => 0,

        'hgsprintf' => 0,

        'csprintf' => 0,
        'vcsprintf' => 0,
        'execx' => 0,
        'exec_manual' => 0,
        'phutil_passthru' => 0,

        'qsprintf' => 1,
        'vqsprintf' => 1,
        'queryfx' => 1,
        'queryfx_all' => 1,
        'queryfx_one' => 1,
      ),
    );
  }

  public function getLinterSeverityMap() {
    $advice  = ArcanistLintSeverity::SEVERITY_ADVICE;
    $error   = ArcanistLintSeverity::SEVERITY_ERROR;
    $warning = ArcanistLintSeverity::SEVERITY_WARNING;

    return array(
      ArcanistTodoCommentPHPASTLinterRule::ID         => $advice,
      ArcanistCommentSpacingPHPASTLinterRule::ID      => $error,
      ArcanistRaggedClassTreeEdgePHPASTLinterRule::ID => $warning,
    );
  }

}
