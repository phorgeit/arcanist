<?php

/**
 * Phage is a prototype parallel shell tool, like HyperShell.
 * Phage is exposed as a wrapper around `bin/remote`.
 * See https://web.archive.org/web/20241004053014/https://secure.phabricator.com/w/phacility_cluster/phage/
 * or https://web.archive.org/web/20250423050457/https://secure.phabricator.com/T2794
 */
abstract class PhageAgentBootloader extends Phobject {

  abstract public function getName();
  abstract public function getBootCommand();
  abstract public function getBootSequence();

}
