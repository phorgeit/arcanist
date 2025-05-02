#!/usr/bin/env php
<?php

// $arcanist_root = dirname(dirname(dirname(dirname(__FILE__))));
// require_once $arcanist_root.'/support/init/init-script.php';

$cppflags = '';
$arch = php_uname('m');

if ($arch == 'i386' || $arch == 'x86_64') {
  $cppflags .= ' -minline-all-stringops ';
}

echo $cppflags;
echo "\n";
