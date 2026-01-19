<?php

/**
 * Defines a locale for translations.
 *
 * Examples might include "English (US)" or "Japanese".
 */
abstract class PhutilLocale extends Phobject {

  /**
   * Get the local identifier code, like "en_US".
   *
   * @return string Locale identifier code.
   */
  abstract public function getLocaleCode();


  /**
   * Get the human-readable locale name, like "English (US)".
   *
   * @return string Human-readable locale name.
   */
  abstract public function getLocaleName();


  /**
   * Set a fallback locale which can be used as a default if this locale is
   * missing translations.
   *
   * For locales like "English (Great Britain)", missing translations can be
   * sourced from "English (US)".
   *
   * Languages with no other fallback use en_US because that's better
   * than proto-English for untranslated strings.
   *
   * If a string is returned, then that locale and its fallbacks will be
   * processed recursively. If an array is returned, then only the locales
   * in the array will be processed, without recursion.
   *
   * @return array|string|null Locale code of fallback locale(s), or null
   *                           if there are no fallback locales.
   */
  public function getFallbackLocaleCode() {
    return 'en_US';
  }


  /**
   * Select a gender variant for this locale. By default, locales use a simple
   * rule with two gender variants, listed in "<male, female>" order.
   *
   * @param string $variant `PhutilPerson` gender constant.
   * @param array<mixed> $translations List of variants.
   * @return string Variant for use.
   */
  public function selectGenderVariant($variant, array $translations) {
    if ($variant == PhutilPerson::GENDER_FEMININE) {
      return end($translations);
    } else {
      return reset($translations);
    }
  }


  /**
   * Select a plural variant for this locale. By default, locales use a simple
   * rule with two plural variants, listed in "<singular, plural>" order.
   *
   * @param int $variant Plurality of the value.
   * @param array<mixed> $translations List of variants.
   * @return string Variant for use.
   */
  public function selectPluralVariant($variant, array $translations) {
    if ($variant == 1) {
      return reset($translations);
    } else {
      return end($translations);
    }
  }


  /**
   * Flags a locale as silly, like "English (Pirate)".
   *
   * These locales are fun but disastrously inappropriate for serious
   * businesses.
   *
   * @return bool True if this locale is silly.
   */
  public function isSillyLocale() {
    return false;
  }


  /**
   * Flags a locale as a testing locale, like "English (US, ALL CAPS)". These
   * locales are useful for translation development, but not for normal users.
   *
   * @return bool True if this is a locale for testing or development.
   */
  public function isTestLocale() {
    return false;
  }


  /**
   * Indicates that the translator should post-process translations in this
   * locale by calling @{method:didTranslateString}.
   *
   * Doing this incurs a performance penalty, and is not useful for most
   * languages. However, it can be used to implement test translations like
   * "English (US, ALL CAPS)".
   *
   * @return bool True to postprocess strings.
   */
  public function shouldPostProcessTranslations() {
    return false;
  }


  /**
   * Callback for post-processing translations.
   *
   * By default, this callback is not invoked. To activate it, return `true`
   * from @{method:shouldPostProcessTranslations}. Activating this callback
   * incurs a performance penalty.
   *
   * @param string $raw_pattern The raw input pattern.
   * @param string $translated_pattern The selected translation pattern.
   * @param array<mixed> $args The raw input arguments.
   * @param string $result_text The translated string.
   * @return string Post-processed translation string.
   */
  public function didTranslateString(
    $raw_pattern,
    $translated_pattern,
    array $args,
    $result_text) {
    return $result_text;
  }


  /**
   * Load all available locales.
   *
   * @return array<string, PhutilLocale> Map from codes to locale objects.
   */
  public static function loadAllLocales() {
    static $locales;

    if ($locales === null) {
      $objects = id(new PhutilClassMapQuery())
        ->setAncestorClass(__CLASS__)
         // Continue on failure so that `arc liberate` can still run
         // if you delete a locale file
        ->setContinueOnFailure(true)
        ->execute();

      $locale_map = array();
      foreach ($objects as $object) {
        $locale_code = $object->getLocaleCode();
        if (empty($locale_map[$locale_code])) {
          $locale_map[$locale_code] = $object;
        } else {
          throw new Exception(
            pht(
              'Two subclasses of "%s" ("%s" and "%s") define '.
              'locales with the same locale code ("%s"). Each locale must '.
              'have a unique locale code.',
              __CLASS__,
              get_class($object),
              get_class($locale_map[$locale_code]),
              $locale_code));
        }
      }

      foreach ($locale_map as $locale_code => $locale) {
        $fallback_codes = $locale->getFallbackLocaleCode();
        if (is_string($fallback_codes)) {
          $fallback_codes = array($fallback_codes);
        }
        if ($fallback_codes !== null) {
          foreach ($fallback_codes as $fallback_code) {
            if (empty($locale_map[$fallback_code])) {
              throw new Exception(
                pht(
                  'The locale "%s" has an invalid fallback locale code '.
                  '("%s"). No locale class exists which defines this locale.',
                  get_class($locale),
                  $fallback_code));
            }
          }
        }
      }

      foreach ($locale_map as $locale_code => $locale) {
        $seen = array($locale_code => get_class($locale));
        self::checkLocaleFallback($locale_map, $locale, $seen);
      }

      $locales = $locale_map;
    }
    return $locales;
  }


  /**
   * Load a specific locale using a locale code.
   *
   * @param string $locale_code Locale code.
   * @return PhutilLocale Locale object.
   */
  public static function loadLocale($locale_code) {
    $all_locales = self::loadAllLocales();
    $locale = idx($all_locales, $locale_code);

    if (!$locale) {
      throw new Exception(
        pht(
          'There is no locale with the locale code "%s".',
          $locale_code));
    }

    return $locale;
  }


  /**
   * Recursively check locale fallbacks for cycles.
   *
   * @param array<string, PhutilLocale> $map Map of locales.
   * @param PhutilLocale $locale Current locale.
   * @param array<string, string> $seen Map of visited locales.
   * @return void
   */
  private static function checkLocaleFallback(
    array $map,
    PhutilLocale $locale,
    array $seen) {

    $fallback_code = $locale->getFallbackLocaleCode();
    if ($fallback_code === null) {
      return;
    }
    if (is_array($fallback_code)) {
      // The locale overrode the standard recursive fallback machinery and took
      // matters into its own hands. If this results in loops, so be it.
      return;
    }

    if (isset($seen[$fallback_code])) {
      $seen[] = get_class($locale);
      $seen[] = pht('...');
      throw new Exception(
        pht(
          'Locale "%s" is part of a cycle of locales which fall back on '.
          'one another in a loop (%s). Locales which fall back on other '.
          'locales must not loop.',
          get_class($locale),
          implode(' -> ', $seen)));
    }

    $seen[$fallback_code] = get_class($locale);
    self::checkLocaleFallback($map, $map[$fallback_code], $seen);
  }

}
