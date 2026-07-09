<?php

final class ArcanistUnitConsoleReporter extends ArcanistUnitReporter {

  private $renderer;
  private $console;
  private $reportPassing = true;

  public function reportFailedOnly() {
    $this->reportPassing = false;
    return $this;
  }

  public function setRenderer($renderer) {
    $this->renderer = $renderer;
    return $this;
  }

  public function setConsole($console) {
    $this->console = $console;
    return $this;
  }

  public function reportUnitResult(ArcanistUnitTestResult $result) {
    if (!$this->shouldReport($result)) {
      return;
    }

    $this->console->writeOut('%s', $this->renderer->renderUnitResult($result));
  }

  private function shouldReport(ArcanistUnitTestResult $result) {
    switch ($result->getResult()) {
      case ArcanistUnitTestResult::RESULT_FAIL:
      case ArcanistUnitTestResult::RESULT_BROKEN:
      case ArcanistUnitTestResult::RESULT_UNSOUND:
      default:
        return true;

      case ArcanistUnitTestResult::RESULT_PASS:
      case ArcanistUnitTestResult::RESULT_SKIP:
        return $this->reportPassing;
    }
  }

}
