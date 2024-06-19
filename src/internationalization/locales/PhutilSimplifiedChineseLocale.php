<?php

/**
 * Locale for "Chinese (Simplified)".
 */
final class PhutilSimplifiedChineseLocale extends PhutilLocale {

  public function getLocaleCode() {
    return 'zh_Hans';
  }

  public function getLocaleName() {
    return pht('Chinese (Simplified)');
  }

  public function getFallbackLocaleCode() {
    // Ideally this would be zh_Hant but Phabricator does not support
    //  bidirectional fallbacks
    // (zh_Hant -> zh_Hans and zh_Hans -> zh_Hant simultaneously)
    // arbitrarily choose to fall back in the other direction instead
    // In the mean time return `en_US` so users don't see Proto-English
    return 'en_US';
  }

}
