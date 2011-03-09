<?php

class HandyLogger {

  //4:all, 1: error only, 0: none
  public static $iLevel = 4;

  public static function error($sMessage) {
    return self::log($sMessage, "error");
  }
  public static function warn($sMessage) {
    return self::log($sMessage, "warn");
  }
  public static function info($sMessage) {
    return self::log($sMessage, "info");
  }
  public static function debug($sMessage) {
    return self::log($sMessage, "debug");
  }
  /**
   * Log
   * @param String $sMessage
   * @param String $sType: error/warn/info/debug: 1,2,3,4
   */
  public static function log($sMessage, $sType="info") {
    static $log = null;
    $log = is_null($log) ? $log = new HandyLogger() : $log;
    switch($sType) {
      case "error":
        $iLevel = 1; break;
      case "warn":
        $iLevel = 2; break;
      case "info":
        $iLevel = 3; break;
      case "debug":
        $iLevel = 4; break;
      default:
        throw new Exception(__METHOD__ . ":Unknown type: " . $sType);
    }
    if (self::$iLevel >= $iLevel) { $log->_log($sMessage, $sType); }
  }

  public function _log($sMessage, $sType) {
    $msg = nl2br(date("Y-m-d.H:i:s") . ":" . $sType.":" . $sMessage . "\n");
    echo $msg; return $msg;
  }
}

?>
