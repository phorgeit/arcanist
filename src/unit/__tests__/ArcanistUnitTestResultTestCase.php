<?php

final class ArcanistUnitTestResultTestCase extends PhutilTestCase {

  public function testCoverageMerges() {
    $cases = array(
      array(
        'coverage' => array(),
        'expect' => null,
      ),
      array(
        'coverage' => array(
          'UUUNCNC',
        ),
        'expect' => 'UUUNCNC',
      ),
      array(
        'coverage' => array(
          'UUCUUU',
          'UUUUCU',
        ),
        'expect' => 'UUCUCU',
      ),
      array(
        'coverage' => array(
          'UUCCCU',
          'UUUCCCNNNC',
        ),
        'expect' => 'UUCCCCNNNC',
      ),
    );

    foreach ($cases as $case) {
      $input = $case['coverage'];
      $expect = $case['expect'];

      $actual = ArcanistUnitTestResult::mergeCoverage($input);

      $this->assertEqual($expect, $actual);
    }
  }

  public function testRenderer() {
    $result = new ArcanistUnitTestResult();
    $result->setName('RendererTest');
    $result->setResult('pass');
    $result->setDuration(0.001);
    $result->setUserData('');

    $renderer = new ArcanistUnitConsoleRenderer();
    $output = $renderer->renderUnitResult($result);
    $test_dscr = 'Renderer copes with null namespace';
    $this->assertTrue((bool)preg_match('/PASS/', $output), $test_dscr);
  }

}
