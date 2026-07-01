<?php

function phutil_date_format($epoch) {
  $now = time();
  $shift = 30 * 24 * 60 * 60;
  if ($epoch < $now + $shift && $epoch > $now - $shift) {
    $format = pht('D, M j');
  } else {
    $format = pht('M j Y');
  }
  return $format;
}

function phutil_format_relative_time($duration) {
  return phutil_format_units_generic(
    $duration,
    array(60, 60, 24, 7),
    array('s', 'm', 'h', 'd', 'w'),
    $precision = 0);
}

/**
 * Format a relative time (duration) into years and days.
 * If it's less than a year, then fall back to
 * phutil_format_relative_time_detailed.
 * This function takes the start and end time to handle leap years, etc.
 * @param int $start Start time as a unix timestamp
 * @param int $end End time as a unix timestamp
 * @return string Human-readable description
 */
function phutil_format_years($start, $end) {
  $date_diff = date_diff(new DateTime('@'.$end), new DateTime('@'.$start));
  // Write this out in full for easier internationalization
  if ($date_diff->y > 0) {
    if ($date_diff->m > 0) {
      if ($date_diff->d > 0) {
         return pht(
           '%s y, %s m, %s d',
           $date_diff->y,
           $date_diff->m,
           $date_diff->d);
      }
      return pht('%s y, %s m', $date_diff->y, $date_diff->m);
    }
    if ($date_diff->d > 0) {
      return pht('%s y, %s d', $date_diff->y, $date_diff->d);
    }
    // Intentionally ignore any sub-day times - "1 year, one hour ago" sounds
    // odd and if it's been a year you don't care how many hours it was ...
    return pht('%s y', $date_diff->y);
  }
  // Not at least a year, fall back to weeks/days/hours/minutes
  return phutil_format_relative_time_detailed($end - $start);
}

/**
 * Format a relative time (duration) into weeks, days, hours, minutes,
 * seconds, but unlike phabricator_format_relative_time, does so for more than
 * just the largest unit.
 *
 * @param int $duration Duration in seconds.
 * @param int $levels (optional) Levels to render. By default, renders the
 *            two highest levels, ie: 5 h, 37 m
 * @return string Human-readable description.
 */
function phutil_format_relative_time_detailed($duration, $levels = 2) {
  if ($duration == 0) {
    return 'now';
  }
  $levels = max(1, min($levels, 5));
  $remainder = 0;

  $is_negative = false;
  if ($duration < 0) {
    $is_negative = true;
    $duration = abs($duration);
  }

  $this_level = 1;
  $detailed_relative_time = phutil_format_units_generic(
    $duration,
    array(60, 60, 24, 7),
    array('s', 'm', 'h', 'd', 'w'),
    $precision = 0,
    $remainder);
  $duration = $remainder;

  while ($remainder > 0 && $this_level < $levels) {
    $detailed_relative_time .= ', '.phutil_format_units_generic(
      $duration,
      array(60, 60, 24, 7),
      array('s', 'm', 'h', 'd', 'w'),
      $precision = 0,
      $remainder);
    $duration = $remainder;
    $this_level++;
  }

  if ($is_negative) {
    $detailed_relative_time .= ' ago';
  }

  return $detailed_relative_time;
}

/**
 * Format a byte count for human consumption, e.g. "10MB" instead of
 * "10485760".
 *
 * @param int $bytes Number of bytes.
 * @return string Human-readable description.
 */
function phutil_format_bytes($bytes) {
  return phutil_format_units_generic(
    $bytes,
    array(1024, 1024, 1024, 1024, 1024),
    array('B', 'KB', 'MB', 'GB', 'TB', 'PB'),
    $precision = 0);
}


/**
 * Parse a human-readable byte description (like "6MB") into an integer.
 *
 * @param string  $input Human-readable description.
 * @return int|null  Number of represented bytes.
 */
function phutil_parse_bytes($input) {
  $bytes = trim($input);
  if (!strlen($bytes)) {
    return null;
  }

  // NOTE: Assumes US-centric numeral notation.
  $bytes = preg_replace('/[ ,]/', '', $bytes);

  $matches = null;
  if (!preg_match('/^(?:\d+(?:[.]\d+)?)([kmgtp]?)b?$/i', $bytes, $matches)) {
    throw new Exception(pht("Unable to parse byte size '%s'!", $input));
  }

  $scale = array(
    'k' => 1024,
    'm' => 1024 * 1024,
    'g' => 1024 * 1024 * 1024,
    't' => 1024 * 1024 * 1024 * 1024,
    'p' => 1024 * 1024 * 1024 * 1024 * 1024,
  );

  $bytes = (float)$bytes;
  if ($matches[1]) {
    $bytes *= $scale[strtolower($matches[1])];
  }

  return (int)$bytes;
}


function phutil_format_units_generic(
  $n,
  array $scales,
  array $labels,
  $precision  = 0,
  &$remainder = null) {

  $is_negative = false;
  if ($n < 0) {
    $is_negative = true;
    $n = abs($n);
  }

  $remainder = 0;
  $accum = 1;

  $scale = array_shift($scales);
  $label = array_shift($labels);
  while ($n >= $scale && count($labels)) {
    $remainder += ((int)$n % $scale) * $accum;
    $n /= $scale;
    $accum *= $scale;
    $label = array_shift($labels);
    if (!count($scales)) {
      break;
    }
    $scale = array_shift($scales);
  }

  if ($is_negative) {
    $n = -$n;
    $remainder = -$remainder;
  }

  if ($precision) {
    $num_string = number_format($n, $precision);
  } else {
    $num_string = (int)floor($n);
  }

  if ($label) {
    $num_string .= ' '.$label;
  }

  return $num_string;
}
