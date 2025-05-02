<?php

/**
 * Translate a string. It uses a translator set by
 * `PhutilTranslator::setInstance()` or translations specified by
 * `PhutilTranslator::getInstance()->setTranslations()` and language rules set
 * by `PhutilTranslator::getInstance()->setLocale()`.
 *
 * @param string $text Translation identifier with `sprintf()` placeholders.
 * @param mixed $variant (optional) Value to select the variant from (e.g.
 *    singular or plural). Defaults to null.
 * @param mixed $variant,... Next values referenced from $text.
 * @return string Translated string with substituted values.
 */
function pht($text, $variant = null /* , ... */) {
  $args = func_get_args();
  $translator = PhutilTranslator::getInstance();
  return call_user_func_array(array($translator, 'translate'), $args);
}

/**
 * Count all elements in an array, or something in an object.
 *
 * @param  array|Countable  $countable A countable object.
 * @return PhutilNumber     Returns the number of elements in the input
 *                          parameter.
 */
function phutil_count($countable) {
  if (!(is_array($countable) || $countable instanceof Countable)) {
    throw new InvalidArgumentException(pht('Argument should be countable.'));
  }

  return new PhutilNumber(count($countable));
}

/**
 * Provide a gendered argument to the translation engine.
 *
 * This function does nothing and only serves as a marker for the static
 * extractor so it knows particular arguments may vary on gender.
 *
 * @param PhutilPerson $person Something implementing
 *   @{interface:PhutilPerson}.
 * @return PhutilPerson The argument, unmodified.
 */
function phutil_person(PhutilPerson $person) {
  return $person;
}

function pht_list(array $items) {
  return implode(', ', $items);
}
