<?php

/**
 * Locale for "Portuguese (Brazil)".
 */
final class PhutilPortugueseBrazilLocale extends PhutilLocale {

  public function getLocaleCode() {
    return 'pt_BR';
  }

  public function getLocaleName() {
    return pht('Portuguese (Brazil)');
  }

  public function getFallbackLocaleCode() {
    // This has to return an array so as to disable
    // recursive fallback processing and allow pt_PT and
    // pt_BR to go both ways.
    // Include en_US so if a string isn't translated into either variant
    // it will display English plurals rather than proto-English
    return array('pt_PT', 'en_US');
  }

}
