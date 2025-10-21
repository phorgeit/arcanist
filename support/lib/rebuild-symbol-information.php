#!/usr/bin/env php
<?php

$root = dirname(dirname(dirname(__FILE__)));
require_once $root.'/support/init/init-script.php';

$args = new PhutilArgumentParser($argv);
$args->setTagline(pht('rebuild the symbol-information.json file'));
$args->setSynopsis(<<<EOHELP
    **rebuild-map.php** [__options__] __root__
        Rebuild the symbol-information.json file.

EOHELP
);

$args->parseStandardArguments();
$args->parse(
  array(
    array(
      'name'      => 'version',
      'param'     => 'version',
      'default'   => 'latest',
      'help'      => pht('Use a specific version of llavile/php-compatinfo-db'),
    ),
    array(
      'name'      => 'show',
      'help'      => pht(
        'Print symbol information to stdout instead of writing it to '.
        'the symbol-information.json file.'),
    ),
  ));

const REPO = 'https://github.com/llaville/php-compatinfo-db';

$version = $args->getArg('version');
$target = Filesystem::createTemporaryDirectory();

if (extension_loaded('zip')) {
  $download_path = $target.'/compatinfo-db-'.$version.'.zip';
  id(new PhutilGitHubReleaseDownloader(REPO, $download_path))
    ->setDownloadFormat('zip')
    ->setVersion($version)
    ->download();

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

  if ($version === 'latest') {
    $target .= '/php-compatinfo-db-master';
  } else {
    $target .= '/php-compatinfo-db-'.$version;
  }
} else if (extension_loaded('phar') && extension_loaded('zlib')) {
  $download_path = $target.'/compatinfo-db-'.$version.'.tar.gz';
  id(new PhutilGitHubReleaseDownloader(REPO, $download_path))
    ->setDownloadFormat('tar.gz')
    ->setVersion($version)
    ->download();

  id(new PharData($download_path))->extractTo($target, null, true);

  if ($version === 'latest') {
    $target .= '/php-compatinfo-db-master';
  } else {
    $target .= '/php-compatinfo-db-'.$version;
  }
} else if (Filesystem::binaryExists('git')) {
  git_checkout($target, $version);
} else {
  throw new Exception(
    pht('No viable means to download llaville/compatinfo-db is available.'));
}

$iterator = new RecursiveDirectoryIterator(
  $target.'/data/reference/extension',
  FilesystemIterator::SKIP_DOTS |
  FilesystemIterator::CURRENT_AS_FILEINFO |
  FilesystemIterator::KEY_AS_PATHNAME |
  FilesystemIterator::UNIX_PATHS);

$symbol_information = array(
  '@'.'generated' => true,
  'params' => array(),
  'functions' => array(),
  'classes' => array(),
  'interfaces' => array(),
  'constants' => array(),
  'traits' => array(),
  'methods' => array(),
  'static_methods' => array(),
  'class_constants' => array(),
  // TODO: Can we get this from PHP CompatInfo?
  // See https://github.com/llaville/php-compat-info/issues/185.
  'functions_windows' => array(
    'apache_child_terminate' => false,
    'chroot' => false,
    'lchgrp' => false,
    'lchown' => false,
    'nl_langinfo' => false,
    // Deprecated in PHP 8.1
    'strptime' => false,
    'sys_getloadavg' => false,
    'checkdnsrr' => '5.3.0',
    'dns_get_record' => '5.3.0',
    'fnmatch' => '5.3.0',
    'getmxrr' => '5.3.0',
    'getopt' => '5.3.0',
    'imagecolorclosesthwb' => '5.3.0',
    'inet_ntop' => '5.3.0',
    'inet_pton' => '5.3.0',
    'link' => '5.3.0',
    'linkinfo' => '5.3.0',
    'readlink' => '5.3.0',
    'socket_create_pair' => '5.3.0',
    'stream_socket_pair' => '5.3.0',
    'symlink' => '5.3.0',
    'time_nanosleep' => '5.3.0',
    'time_sleep_until' => '5.3.0',
    // php/doc-en@2a0e3aa7bed3a46b9cc052701579ab2efa1bbc26
    'imagecreatefromxpm' => '5.3.19',
    // https://www.php.net/manual/en/migration70.changed-functions.php
    'getrusage' => '7.0.0',
    // https://www.php.net/manual/en/migration72.new-features.php
    'proc_nice' => '7.2.0',
  ),
);

/** @var SplFileInfo $fileinfo */
foreach (new RecursiveIteratorIterator($iterator) as $path => $fileinfo) {
  if ($fileinfo->isDir()) {
    continue;
  }

  $extension_name = basename(dirname($fileinfo->getPath()));
  $entries = phutil_json_decode(Filesystem::readFile($path));

  switch ($fileinfo->getFilename()) {
    case 'classes.json:':
      $symbol_information['classes'] = map_entries($extension_name, $entries);
      break;
    case 'const.json':
      foreach ($entries as $constant) {
        $class = $constant['class_name'];
        $name = $constant['name'];

        $symbol_information['class_constants'][$class][$name] = map_entry(
          $extension_name,
          $constant);
      }
      break;
    case 'constants.json':
      $symbol_information['constants'] = map_entries($extension_name, $entries);
      break;
    case 'functions.json':
      foreach ($entries as $function) {
        $name = $function['name'];
        $parameters = idx($function, 'parameters', '');
        // Deprecations can be a string or an array, with an optional message
        // key that contains the reason.
        $deprectated = idx($function, 'deprecated', '');

        if ($parameters !== '') {
          foreach (explode(',', $parameters) as $parameter) {
            $symbol_information['params'][$name][] = trim($parameter);
          }
        }

        // Skip entries about moved functions as they tend to overwrite and
        // result in misleading information.
        if (
          is_array($deprectated) &&
          isset($deprectated['message']) &&
          strpos($deprectated['message'], 'was moved') !== false) {
          continue;
        }

        $symbol_information['functions'][$name] = map_entry(
          $extension_name,
          $function);
      }
      break;
    case 'methods.json':
      foreach ($entries as $method) {
        $class = $method['class_name'];
        $name = $method['name'];

        if (!empty($method['static'])) {
          $symbol_information['static_methods'][$class][$name] = map_entry(
            $extension_name,
            $method);
        } else {
          $symbol_information['methods'][$class][$name] = map_entry(
            $extension_name,
            $method);
        }
      }
      break;
    case 'interfaces.json':
      $symbol_information['interfaces'] = map_entries(
        $extension_name,
        $entries);
      break;
    case 'traits.json':
      $symbol_information['traits'] = map_entries($extension_name, $entries);
      break;
  }
}

ksort($symbol_information['params']);
ksort($symbol_information['functions']);
ksort($symbol_information['classes']);
ksort($symbol_information['interfaces']);
ksort($symbol_information['constants']);
ksort($symbol_information['traits']);
ksort($symbol_information['methods']);
ksort($symbol_information['static_methods']);
ksort($symbol_information['class_constants']);

if ($args->getArg('show')) {
  echo id(new PhutilJSON())->encodeFormatted($symbol_information);
} else {
  Filesystem::writeFile(
    $root.'/resources/php/symbol-information.json',
    id(new PhutilJSON())->encodeFormatted($symbol_information));

  echo pht('Done.')."\n";
}

Filesystem::remove($target);

function map_entries(string $extension_name, array $entries) {
  $information = array();

  foreach ($entries as $entry) {
    $name = $entry['name'];
    $information[$name] = map_entry($extension_name, $entry);
  }

  return $information;
}

function map_entry(string $extension_name, array $entry) {
  return array(
    'ext.name' => $extension_name,
    'ext.min'  => idx($entry, 'ext_min'),
    'ext.max'  => idx($entry, 'ext_max'),
    'php.min'  => idx($entry, 'php_min'),
    'php.max'  => idx($entry, 'php_max'),
  );
}

function git_checkout($target, $version) {
  if ($version === 'latest') {
    execx('git clone --single-branch --depth 1 %s %s', REPO, $target);
  } else {
    execx(
      'git clone --single-branch --depth 1 --branch %s %s %s',
      'v'.$version,
      REPO,
      $target);
  }
}
