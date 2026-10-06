<?php


final class PhutilErrorLog
  extends Phobject {

  private $logName;
  private $logPath;

  public function setLogName($log_name) {
    $this->logName = $log_name;
    return $this;
  }

  public function getLogName() {
    return $this->logName;
  }

  public function setLogPath($log_path) {
    $this->logPath = $log_path;
    return $this;
  }

  public function getLogPath() {
    return $this->logPath;
  }

  public function activateLog() {
    $log_path = $this->getLogPath();
    ini_set('error_log', $log_path);
    PhutilErrorHandler::setErrorListener(array($this, 'onError'));
  }

  public function onError($event, $value, array $metadata) {
    // If we've set "error_log" to a real file, messages won't be output to
    // stderr by default. Copy them to stderr.

    if ($this->logPath === null) {
      return;
    }

    $message = idx($metadata, 'default_message');

    if (strlen($message)) {
      $message = tsprintf("%B\n", $message);
      PhutilSystem::writeStderr($message);
    }
  }

}
