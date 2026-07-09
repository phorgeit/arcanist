<?php

abstract class ArcanistUnitReporter extends Phobject {

  abstract public function reportUnitResult(ArcanistUnitTestResult $result);

}
