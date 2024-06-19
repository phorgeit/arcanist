<?php

/**
 * Locale for "Portuguese (Portugal)".
 */
final class PhutilPortuguesePortugalLocale extends PhutilLocale {

  public function getLocaleCode() {
    return 'pt_PT';
  }

  public function getLocaleName() {
    return pht('Portuguese (Portugal)');
  }

  public function getFallbackLocaleCode() {
    // Ideally this would be pt_BR but Phabricator does not support
    // bidirectional fallbacks (pt_BR -> pt and pt -> pt_BR simultaneously)
    // since Translatewiki calls pt_PT "Portugese" without a country
    // it makes slightly more sense to fall back in the other direction
    // In the mean time return `en_US` so users don't see Proto-English
    // unncecessarily
    return 'en_US';
  }

}
