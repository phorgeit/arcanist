<?php

/**
 * Test cases for functions in pht.php.
 */
final class PhutilPhtTestCase extends PhutilTestCase {

  public function testPht() {
    PhutilTranslator::setInstance(new PhutilTranslator());

    $this->assertEqual('beer', pht('beer'));
    $this->assertEqual('1 beer(s)', pht('%d beer(s)', 1));

    $english_locale = PhutilLocale::loadLocale('en_US');
    PhutilTranslator::getInstance()->setLocale($english_locale);
    PhutilTranslator::getInstance()->setTranslations(
      array(
        '%d beer(s)' => array('%d beer', '%d beers'),
      ));

    $this->assertEqual('1 beer', pht('%d beer(s)', 1));

    $czech_locale = PhutilLocale::loadLocale('cs_CZ');
    PhutilTranslator::getInstance()->setLocale($czech_locale);
    PhutilTranslator::getInstance()->setTranslations(
      array(
        '%d beer(s)' => array('%d pivo', '%d piva', '%d piv'),
      ));

    $this->assertEqual('5 piv', pht('%d beer(s)', 5));
  }
}
