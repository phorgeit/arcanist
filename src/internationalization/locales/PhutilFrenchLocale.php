<?php

/**
 * Locale for "French (France)".
 */
final class PhutilFrenchLocale extends PhutilLocale {

  public function getLocaleCode() {
    return 'fr_FR';
  }

  public function getLocaleName() {
    return pht('French (France)');
  }

  public function selectPluralVariant($variant, array $translations) {
    if ($variant == 0 || $variant == 1) {
      return head($translations);
    } else {
      return last($translations);
    }
  }

}
