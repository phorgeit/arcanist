<?php

/**
 * Locale for "Chinese (Traditional)".
 */
final class PhutilTraditionalChineseLocale extends PhutilLocale {

  public function getLocaleCode() {
    return 'zh_Hant';
  }

  public function getLocaleName() {
    return pht('Chinese (Traditional)');
  }

  public function getFallbackLocaleCode() {
    // This has to return an array so as to disable
    // recursive fallback processing and allow zh_Hant and
    // zh_Hans to go both ways.
    // Include en_US so if a string isn't translated into either variant
    // it will display English plurals rather than proto-English
    return array('zh_Hans', 'en_US');
  }

}
