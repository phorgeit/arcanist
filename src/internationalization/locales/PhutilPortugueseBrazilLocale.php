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
    // Phabricator does not support bidirectional
    // fallbacks (pt_BR -> pt and pt -> pt_BR simultaneously)
    // since Translatewiki calls pt_PT "Portugese" without a country
    // it makes slightly more sense to fall back in this direction
    // than the other one
    return 'pt_PT';
  }

}
