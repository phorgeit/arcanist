<?php

/**
 * The default English locale.
 */
final class PhutilUSEnglishLocale extends PhutilLocale {

  public function getLocaleCode() {
    return 'en_US';
  }

  public function getLocaleName() {
    return pht('English (US)');
  }

  public function getFallbackLocaleCode() {
    // The default fallback is en_US, explicitly return null here
    // to avoid a fallback loop
    return null;
  }

}
