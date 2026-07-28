<?php

abstract class ArcanistToolset extends Phobject {

  final public function getToolsetKey() {
    return $this->getPhobjectClassConstant('TOOLSETKEY');
  }

  final public static function newToolsetMap() {
    $toolsets = id(new PhutilClassMapQuery())
      ->setAncestorClass(__CLASS__)
      ->setUniqueMethod('getToolsetKey')
      // Continue on failure so that `arc liberate` can still run
      // if you delete a toolset file
      ->setContinueOnFailure(true)
      ->execute();

    return $toolsets;
  }

  public function getToolsetArguments() {
    return array();
  }

}
