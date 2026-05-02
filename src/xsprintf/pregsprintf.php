<?php

/**
 * Format a regular expression. Supports the following conversions:
 *
 *  %s String
 *    Escapes a string using `preg_quote`.
 *
 *  %R Raw
 *    Inserts a raw regular expression.
 *
 * @param  string       $pattern sprintf()-style format string.
 * @param  string|null  $flags
 * @param  string       ...$args Zero or more arguments.
 * @return string       Formatted string.
 */
function pregsprintf($pattern, $flags = null, ...$args) {
  $delim    = chr(7);
  $userdata = array('delimiter' => $delim);

  array_unshift($args, $pattern);
  $pattern = xsprintf('xsprintf_regex', $userdata, $args);
  return $delim.$pattern.$delim.$flags;
}

/**
 * @{function:xsprintf} callback for regular expressions.
 */
function xsprintf_regex($userdata, &$pattern, &$pos, &$value, &$length) {
  $delim = idx($userdata, 'delimiter');
  $type  = $pattern[$pos];

  switch ($type) {
    case 's':
      $value = preg_quote($value, $delim);
      break;
    case 'R':
      $type = 's';
      break;
  }

  $pattern[$pos] = $type;
}
