<?php

final class ArcanistComposerLinter extends ArcanistLinter {

  const LINT_OUT_OF_DATE = 1;

  public function getInfoName() {
    return pht('Composer Dependency Manager');
  }

  public function getInfoDescription() {
    return pht('A linter for Composer related files.');
  }

  public function getLinterName() {
    return 'COMPOSER';
  }

  public function getLinterConfigurationName() {
    return 'composer';
  }

  public function getLintNameMap() {
    return array(
      self::LINT_OUT_OF_DATE => pht('Lock file out-of-date'),
    );
  }

  public function lintPath($path) {
    switch (basename($path)) {
      case 'composer.json':
        $this->lintComposerJson($path);
        break;
      case 'composer.lock':
        break;
    }
  }

  private function lintComposerJson($path) {
    $composer_hash = self::getContentHash(
      Filesystem::readFile(dirname($path).'/composer.json'));
    $composer_lock = phutil_json_decode(
      Filesystem::readFile(dirname($path).'/composer.lock'));

    if ($composer_hash !== $composer_lock['content-hash']) {
      $this->raiseLintAtPath(
        self::LINT_OUT_OF_DATE,
        pht(
          "The '%s' file seems to be out-of-date. ".
          "You probably need to run `%s`.",
          'composer.lock',
          'composer update'));
    }
  }

  /**
   * Returns the md5 hash of the sorted content of the composer.json file.
   *
   * This function copied from
   * https://github.com/
   * composer/composer/blob/1.5.2/src/Composer/Package/Locker.php
   * and has the following license:
   *
   * Copyright (c) Nils Adermann, Jordi Boggiano
   *
   * Permission is hereby granted, free of charge, to any person obtaining a
   * copy of this software and associated documentation files (the "Software"),
   * to deal in the Software without restriction, including without limitation
   * the rights to use, copy, modify, merge, publish, distribute, sublicense,
   * and/or sell copies of the Software, and to permit persons to whom the
   * Software is furnished to do so, subject to the following conditions:
   *
   * The above copyright notice and this permission notice shall be included in
   * all copies or substantial portions of the Software.
   *
   * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
   * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
   * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
   * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
   * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
   * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
   * DEALINGS IN THE SOFTWARE.
   *
   *
   * @param string $composer_file_contents The contents of the composer file.
   *
   * @return string
   */
  public static function getContentHash($composer_file_contents) {
    $content = json_decode($composer_file_contents, true);

    $relevant_keys = array(
      'name',
      'version',
      'require',
      'require-dev',
      'conflict',
      'replace',
      'provide',
      'minimum-stability',
      'prefer-stable',
      'repositories',
      'extra',
    );

    $relevant_content = array();

    foreach (array_intersect($relevant_keys, array_keys($content)) as $key) {
      $relevant_content[$key] = $content[$key];
    }
    if (isset($content['config']['platform'])) {
      $relevant_content['config']['platform'] = $content['config']['platform'];
    }

    ksort($relevant_content);

    return md5(json_encode($relevant_content));
  }

}
