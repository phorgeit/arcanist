<?php

/**
 * Utilities for wrangling JSON.
 *
 * @task pretty Formatting JSON Objects
 * @task internal Internals
 */
final class PhutilJSON extends Phobject {


/* -(  Formatting JSON Objects  )-------------------------------------------- */


  /**
   * Encode an object in JSON and pretty-print it. This generates a valid JSON
   * object with human-readable whitespace and indentation.
   *
   * @param   dict    $object An object to encode in JSON.
   * @return  string  Pretty-printed object representation.
   */
  public function encodeFormatted($object) {
    return $this->encodeFormattedObject($object, 0)."\n";
  }


  /**
   * Encode a list in JSON and pretty-print it, discarding keys.
   *
   * @param list<wild> $list List to encode in JSON.
   * @return string Pretty-printed list representation.
   */
  public function encodeAsList(array $list) {
    return $this->encodeFormattedArray($list, 0)."\n";
  }


/* -(  Internals  )---------------------------------------------------------- */


  /**
   * Pretty-print a JSON object.
   *
   * @param   dict    $object Object to format.
   * @param   int     $depth Current depth, for indentation.
   * @return  string  Pretty-printed value.
   * @task internal
   */
  private function encodeFormattedObject($object, $depth) {
    if ($object instanceof stdClass) {
      $object = (array)$object;
    }

    if (empty($object) || !is_iterable($object)) {
      return '{}';
    }

    $pre = $this->getIndent($depth);
    $key_pre = $this->getIndent($depth + 1);
    $keys = array();
    $vals = array();
    $max = 0;
    foreach ($object as $key => $val) {
      $ekey = $this->encodeFormattedValue((string)$key, 0);
      $max = max($max, strlen($ekey));
      $keys[] = $ekey;
      $vals[] = $this->encodeFormattedValue($val, $depth + 1);
    }
    $key_lines = array();
    foreach ($keys as $k => $key) {
      $key_lines[] = $key_pre.$key.': '.$vals[$k];
    }
    $key_lines = implode(",\n", $key_lines);

    $out  = "{\n";
    $out .= $key_lines;
    $out .= "\n";
    $out .= $pre.'}';

    return $out;
  }


  /**
   * Pretty-print a JSON list.
   *
   * @param   list    $array List to format.
   * @param   int     $depth Current depth, for indentation.
   * @return  string  Pretty-printed value.
   * @task internal
   */
  private function encodeFormattedArray($array, $depth) {
    if (empty($array)) {
      return '[]';
    }

    $pre = $this->getIndent($depth);
    $val_pre = $this->getIndent($depth + 1);

    $vals = array();
    foreach ($array as $val) {
      $vals[] = $val_pre.$this->encodeFormattedValue($val, $depth + 1);
    }
    $val_lines = implode(",\n", $vals);

    $out  = "[\n";
    $out .= $val_lines;
    $out .= "\n";
    $out .= $pre.']';

    return $out;
  }


  /**
   * Pretty-print a JSON value.
   *
   * @param   dict    $value Value to format.
   * @param   int     $depth Current depth, for indentation.
   * @return  string  Pretty-printed value.
   * @task internal
   */
  private function encodeFormattedValue($value, $depth) {
    if (is_array($value)) {
      if (phutil_is_natural_list($value)) {
        return $this->encodeFormattedArray($value, $depth);
      } else {
        return $this->encodeFormattedObject($value, $depth);
      }
    } else if (is_object($value)) {
      return $this->encodeFormattedObject($value, $depth);
    } else {
      if (defined('JSON_UNESCAPED_SLASHES')) {
        // If we have PHP >= 5.4.0 && the JSON extension is installed (as of
        // PHP 8.0.0, it is a core PHP extension), disable escaping of slashes
        // when pretty-printing values. Escaping slashes can defuse an attack
        // where the attacker embeds "</script>" inside a JSON string, but that
        // isn't relevant when rendering JSON for human viewers.
        return json_encode($value, JSON_UNESCAPED_SLASHES);
      } else {
        return json_encode($value);
      }
    }
  }


  /**
   * Render a string corresponding to the current indent depth.
   *
   * @param   int     $depth Current depth.
   * @return  string  Indentation.
   * @task internal
   */
  private function getIndent($depth) {
    if (!$depth) {
      return '';
    } else {
      return str_repeat('  ', $depth);
    }
  }

}
