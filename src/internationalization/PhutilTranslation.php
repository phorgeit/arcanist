<?php

abstract class PhutilTranslation extends Phobject {

  /**
   * Get the locale code which this class translates text for, like
   * "en_GB".
   *
   * This should correspond to a valid subclass of @{class:PhutilLocale}.
   *
   * @return string Locale code for this translation.
   */
  abstract public function getLocaleCode();


  /**
   * Return a map of all translations.
   *
   * @return array<string,mixed> Map of raw strings to translations.
   */
  abstract protected function getTranslations();


  /**
   * Return a filtered map of all strings in this translation.
   *
   * Filters out empty/placeholder translations.
   *
   * @return array<string,mixed> Map of raw strings to translations.
   */
  final public function getFilteredTranslations() {
    $translations = $this->getTranslations();

    foreach ($translations as $key => $translation) {
      if ($translation === null) {
        unset($translations[$key]);
      }
    }

    return $translations;
  }


  /**
   * Load all available translation objects.
   *
   * @return array<PhutilTranslation> List of available translation sources.
   */
  public static function loadAllTranslations() {
    return id(new PhutilClassMapQuery())
      ->setAncestorClass(__CLASS__)
      // Continue on failure so that `arc liberate` can still run
      // if you delete a translation file
      ->setContinueOnFailure(true)
      ->execute();
  }


  private static function getTranslationMapForLocaleNoFallback($locale_code) {
    $translations = self::loadAllTranslations();

    $results = array();
    foreach ($translations as $translation) {
      if ($translation->getLocaleCode() === $locale_code) {
        $results += $translation->getFilteredTranslations();
      }
    }
    return $results;
  }

  /**
   * Return all translations as a nested array.
   *
   * @return array<string,array<string,string|array>>
   *  List of available translations in the format
       [language => [proto-English => translation ] ]
   */
  public static function getAllTranslations() {
    $translations = self::loadAllTranslations();
    $results = array();
    foreach ($translations as $translation) {
      $code = $translation->getLocaleCode();
      if (!isset($results[$code])) {
        $results[$code] = array();
      }
      $results[$code] += $translation->getFilteredTranslations();
    }
    return $results;
 }

  /**
   * Load the complete translation map for a locale.
   *
   * This will compile primary and fallback translations into a single
   * translation map.
   *
   * @param string $locale_code Locale code, like "en_US".
   * @return array<string,mixed> Map of all available translations.
   */
  public static function getTranslationMapForLocale($locale_code) {
    $locale = PhutilLocale::loadLocale($locale_code);

    $results = self::getTranslationMapForLocaleNoFallback($locale_code);
    $fallback_code = $locale->getFallbackLocaleCode();
    if ($fallback_code !== null) {
      if (is_array($fallback_code)) {
        foreach ($fallback_code as $one_fallback) {
          $results += self::getTranslationMapForLocaleNoFallback(
            $one_fallback);
        }
      } else {
        $results += self::getTranslationMapForLocale($fallback_code);
     }
    }
    return $results;
  }

}
