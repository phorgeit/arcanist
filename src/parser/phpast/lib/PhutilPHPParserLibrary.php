<?php

final class PhutilPHPParserLibrary extends Phobject {

  /**
   * The expected PHP-Parser version for PHP < 7.4.
   *
   * This is the version that would be obtained by downloading and including an
   * up-to-date PHP-parser. The //actual// PHP-parser version may vary.
   */
  const EXPECTED_VERSION_LEGACY = '4.19.5';

  /**
   * The expected PHP-Parser version for PHP >= 7.4.
   *
   * This is the version that would be obtained by downloading and including an
   * up-to-date PHP-parser. The //actual// PHP-parser version may vary.
   */
  const EXPECTED_VERSION = '5.7.0';

  const REPO = 'https://github.com/nikic/PHP-Parser';

  /**
   * The expected md5 hash of the PHP-parser packages listed above.
   */
  private static $hashes = array(
    // v4.19.5.tar.gz
    '12debc62a3f7588c182f0d02ba07996e',
    // v4.19.5.zip
    '06a986aeff2a08b615624421de64c4c4',
    // v5.7.0.tar.gz
    'f997f0fb2168894b99f1b959ade13eae',
    // v5.7.0.zip
    '1ab96406751d61afa922a112895c9f24',
  );

  private static $version;

  private static function downloadPHPParser(
    string $path,
    string $version,
    string $extension) {

    $path .= '/php-parser-'.$version.$extension;

    // Skip downloading if the file already exists and matches the hash.
    if (
      Filesystem::pathExists($path) &&
      in_array(md5_file($path), self::$hashes, true)) {

      return $path;
    }

    // HTTPSFuture::setDownloadPath refuses to overwrite.
    Filesystem::remove($path);

    $future = new HTTPSFuture(
      self::REPO.'/archive/refs/tags/v'.$version.$extension);
    $future
      ->setDownloadPath($path)
      ->resolvex();

    $actual_md5 = md5_file($path);

    if (!in_array($actual_md5, self::$hashes, true)) {
      $expected = implode(', ', self::$hashes);

      throw new Exception(
        "PHP-parser hash does not match: expected any of {$expected}".
        ", got {$actual_md5}.");
    }

    return $path;
  }

  public static function build() {
    $root = phutil_get_library_root('arcanist');
    $path = Filesystem::resolvePath($root.'/../support/php-parser');
    $target = self::getPath();

    if (PHP_VERSION < 70400) {
      $version = self::EXPECTED_VERSION_LEGACY;
    } else {
      $version = self::EXPECTED_VERSION;
    }

    if (extension_loaded('zip')) {
      $download_path = self::downloadPHPParser($path, $version, '.zip');

      $zip = new ZipArchive();
      $result = $zip->open($download_path);
      if (!$result) {
        throw new Exception(
          pht(
            'Opening %s failed! %s.',
            $download_path,
            $result === false ? 'Unknown Error' : (string)$result));
      }

      $zip->extractTo($target);

      // Renames fail if the target directory exists.
      Filesystem::remove("{$target}/PhpParser");

      Filesystem::rename(
        "{$target}/PHP-Parser-{$version}/lib/PhpParser",
        "{$target}/PhpParser");

      Filesystem::remove("{$target}/PHP-Parser-{$version}");
    } else if (
      extension_loaded('phar') &&
      extension_loaded('zlib')) {

      $download_path = self::downloadPHPParser($path, $version, '.tar.gz');

      id(new PharData($download_path))->extractTo($target, null, true);

      // Renames fail if the target directory exists.
      Filesystem::remove("{$target}/PhpParser");

      Filesystem::rename(
        "{$target}/PHP-Parser-{$version}/lib/PhpParser",
        "{$target}/PhpParser");

      Filesystem::remove("{$target}/PHP-Parser-{$version}");
    } else if (Filesystem::binaryExists('git')) {
      self::gitCheckout($target, $version);
    } else {
      throw new Exception(
        pht('No viable means to download PHP-parser is available.'));
    }

    Filesystem::writeFile($target.'/version', $version);
  }

  private static function gitCheckout(string $path, string $version) {
    execx(
      'git clone --single-branch --depth 1 --branch %s %s %s',
      'v'.$version,
      self::REPO,
      $path);

    Filesystem::remove($path.'/.git');
  }

  /**
   * Returns human-readable instructions for building PHP-parser.
   *
   * @return string
   */
  public static function getBuildInstructions() {
    $root = phutil_get_library_root('arcanist');
    $script = Filesystem::resolvePath(
      $root.'/../support/php-parser/build-php-parser.php');

    return phutil_console_format(
      "%s:\n\n  \$ %s\n",
      pht(
        "Your version of '%s' is unbuilt or out of date. Run this ".
        "script to build it.",
        'php-parser'),
      $script);
  }

  private static function phpParserAutoloader($classname) {
    $lib = self::getPath();

    if (strpos($classname, 'PhpParser') !== 0) {
      return false;
    }

    $path = $lib.'/'.str_replace('\\', '/', $classname).'.php';

    if (!Filesystem::pathExists($path)) {
      return false;
    }

    require $path;

    return true;
  }

  /**
   * Get a suitable Parser instance.
   *
   * @phutil-external-symbol class PhpParser\ParserFactory
   *
   * @return PhpParser\Parser
   */
  public static function getParser() {
    static $parser = null;

    if (!$parser) {
      if (!self::isAvailable()) {
        try {
          self::build();
        } catch (Exception $ex) {
          throw new Exception(self::getBuildInstructions(), 0, $ex);
        }
      }

      spl_autoload_register('PhutilPHPParserLibrary::phpParserAutoloader');

      $parser = id(new PhpParser\ParserFactory())
        ->createForNewestSupportedVersion();
    }

    return $parser;
  }

  /**
   * Returns the path to the PHP-Parser library.
   *
   * @return string
   */
  public static function getPath() {
    static $path = null;

    if (!$path) {
      $root = phutil_get_library_root('arcanist');
      $path = Filesystem::resolvePath($root.'/../support/php-parser/lib');
    }

    return $path;
  }

  /**
   * Returns the PHP-parser version.
   *
   * @return string
   */
  public static function getVersion() {
    if (self::$version === null) {
      $lib = self::getPath();

      if (Filesystem::pathExists($lib.'/version')) {
        self::$version = trim(Filesystem::readFile($lib.'/version'));
      }
    }

    return self::$version;
  }

  /**
   * Checks if PHP-parser is built and up-to-date.
   *
   * @return bool
   */
  public static function isAvailable() {
    $version = self::getVersion();
    return $version === self::EXPECTED_VERSION ||
      $version === self::EXPECTED_VERSION_LEGACY;
  }

}
