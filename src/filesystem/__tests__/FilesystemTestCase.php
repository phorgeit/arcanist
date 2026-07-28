<?php

final class FilesystemTestCase extends PhutilTestCase {

  public function testBinaryExists() {
    // Test for the `which` binary on Linux, and the `where` binary on Windows,
    // because `which which` is cute.

    if (phutil_is_windows()) {
      $exists = 'where';
    } else {
      $exists = 'which';
    }

    $this->assertEqual(
      true,
      Filesystem::binaryExists($exists));

    // We don't expect to find this binary on any system.

    $this->assertEqual(
      false,
      Filesystem::binaryExists('halting-problem-decider'));
  }

  public function testResolveBinary() {
    // Test to make sure resolveBinary() returns the full path to the `which`
    // and `where` binaries.

    if (phutil_is_windows()) {
      $binary = 'where';
    } else {
      $binary = 'which';
    }

    $path = Filesystem::resolveBinary($binary);
    $this->assertFalse(null === $path);
    $this->assertTrue(file_exists($path));
    $this->assertFalse(is_dir($path));

    $this->assertEqual(null,
      Filesystem::resolveBinary('halting-problem-decider'));
  }

  public function testWriteUniqueFile() {
    $tmp = new TempFile();
    $dir = dirname($tmp);

    // Writing an empty file should work.
    $f = Filesystem::writeUniqueFile($dir, '');
    $this->assertEqual('', Filesystem::readFile($f));

    // File name should be unique.
    $g = Filesystem::writeUniqueFile($dir, 'quack');
    $this->assertTrue($f != $g);
  }

  public function testReadRandomBytes() {
    $number_of_bytes = 1024;
    $data = Filesystem::readRandomBytes($number_of_bytes);
    $this->assertTrue(strlen($data) == $number_of_bytes);

    $data1 = Filesystem::readRandomBytes(128);
    $data2 = Filesystem::readRandomBytes(128);
    $this->assertFalse($data1 == $data2);

    $caught = null;
    try {
      Filesystem::readRandomBytes(0);
    } catch (Exception $ex) {
      $caught = $ex;
    }
    $this->assertTrue($caught instanceof Exception);
  }

  public function testWalkToRoot() {
    $test_cases = array(
      array(
        dirname(__FILE__).'/data/include_dir.txt/subdir.txt/test',
        dirname(__FILE__),
        array(
          dirname(__FILE__).'/data/include_dir.txt/subdir.txt/test',
          dirname(__FILE__).'/data/include_dir.txt/subdir.txt',
          dirname(__FILE__).'/data/include_dir.txt',
          dirname(__FILE__).'/data',
          dirname(__FILE__),
        ),
      ),
      array(
        dirname(__FILE__).'/data/include_dir.txt/subdir.txt',
        dirname(__FILE__),
        array(
          dirname(__FILE__).'/data/include_dir.txt/subdir.txt',
          dirname(__FILE__).'/data/include_dir.txt',
          dirname(__FILE__).'/data',
          dirname(__FILE__),
        ),
      ),

      'root and path are identical' => array(
        dirname(__FILE__),
        dirname(__FILE__),
        array(
          dirname(__FILE__),
        ),
      ),

      'root is not an ancestor of path' => array(
        dirname(__FILE__),
        dirname(__FILE__).'/data/include_dir.txt/subdir.txt',
        array(),
      ),

      'fictional paths work' => array(
        '/x/y/z',
        '/',
        array(
          '/x/y/z',
          '/x/y',
          '/x',
          '/',
        ),
      ),

    );

    foreach ($test_cases as $test_case) {
      list($path, $root, $expected) = $test_case;

      // On Windows, paths will have backslashes rather than forward slashes.
      // Normalize our expectations to the path format for the environment.
      foreach ($expected as $key => $epath) {
        $expected[$key] = str_replace('/', DIRECTORY_SEPARATOR, $epath);
      }

      $this->assertEqual(
        $expected,
        Filesystem::walkToRoot($path, $root));
    }
  }

  public function testisDescendant() {
    $test_cases = array(
      array(
        __FILE__,
        dirname(__FILE__),
        true,
      ),
      array(
        dirname(__FILE__),
        dirname(dirname(__FILE__)),
        true,
      ),
      array(
        dirname(__FILE__),
        phutil_get_library_root_for_path(__FILE__),
        true,
      ),
      array(
        dirname(dirname(__FILE__)),
        dirname(__FILE__),
        false,
      ),
      array(
        dirname(__FILE__).'/quack',
        dirname(__FILE__),
        false,
      ),
    );

    foreach ($test_cases as $test_case) {
      list($path, $root, $expected) = $test_case;

      $this->assertEqual(
        $expected,
        Filesystem::isDescendant($path, $root),
        sprintf(
          'Filesystem::isDescendant(%s, %s)',
          phutil_var_export($path),
          phutil_var_export($root)));
    }
  }

  public function testRandomIntegers() {
    $valid_ranges = array(
      array(5, 5),
      array(-1, 1),
      array(0, 10000),
      array(0, 999999999),
      array(-65535, 65536),
    );

    foreach ($valid_ranges as $case) {
      list($min, $max) = $case;

      $result = Filesystem::readRandomInteger($min, $max);

      $this->assertTrue($min <= $result, pht('%d <= %d', $min, $result));
      $this->assertTrue($max >= $result, pht('%d >= %d', $max, $result));
    }

    $invalid_ranges = array(
      array('1', '2'),
      array(1.0, 2.0),
      array(5, 3),
    );

    foreach ($invalid_ranges as $case) {
      list($min, $max) = $case;

      $caught = null;
      try {
        Filesystem::readRandomInteger($min, $max);
      } catch (Throwable $ex) {
        $caught = $ex;
      }

      $this->assertTrue($caught instanceof Exception);
    }
  }


  public function testNormalizeVirtualPath() {
    $cls = new ReflectionClass(Filesystem::class);
    $normalize = $cls->getMethod('normalizeVirtualPath');
    if (PHP_VERSION_ID < 80100) {
      $normalize->setAccessible(true);
    }

    $valids = array(
      '/a/b/c/../d/e//./f' => 'a/b/d/e/f',
      'x/' => 'x',
      '///' => '',
      '' => '',
      '.foo/bar/..baz///' => '.foo/bar/..baz',
    );

    foreach ($valids as $input => $expected) {
      $input = str_replace('/', DIRECTORY_SEPARATOR, $input);
      $expected = str_replace('/', DIRECTORY_SEPARATOR, $expected);
      $actual = $normalize->invoke(null, $input);

      $this->assertEqual($expected, $actual);
    }

    $invalids = array(
      'foo/bar/../baz/../../../run',
      '/../',
    );

    foreach ($invalids as $input) {
      $caught = null;
      try {
        $input = str_replace('/', DIRECTORY_SEPARATOR, $input);
        $normalize->invoke(null, $input);
      } catch (Throwable $ex) {
        $caught = $ex;
      }
      $this->assertCaught(FilesystemException::class, $caught);
    }

  }

  public function testParsePharPath() {

    if (phutil_is_windows()) {
      $this->assertSkipped(
        pht("I'm not sure what valid inputs look like in Windows."));
    }


    $cls = new ReflectionClass(Filesystem::class);
    $parser = $cls->getMethod('parsePharUri');
    if (PHP_VERSION_ID < 80100) {
      $parser->setAccessible(true);
    }

    $p = 'phar://';

    $valid_cases = array(
      $p.'/foo/bar.phar/' => array('/foo/bar.phar', '/'),
      $p.'/foo/bar.phar/x/y/' => array('/foo/bar.phar', '/x/y'),
      $p.'relative/archive.phar/my.file' =>
        array('relative/archive.phar', '/my.file'),
    );

    foreach ($valid_cases as $input => $expected) {
      $actual = $parser->invoke(null, $input);
      $this->assertEqual($expected, $actual);
    }

    $invalid_cases = array(
      $p,
      $p.'/',
      '/foo.phar/bar',
      'baz.phar',
    );

    foreach ($invalid_cases as $input) {
      $caught = null;
      try {
        $parser->invoke(null, $input);
      } catch (Throwable $ex) {
        $caught = $ex;
      }
      $this->assertCaught(
        FilesystemException::class,
        $caught,
        "for input {$input}");
    }
  }

}
