<?php
require_once(dirname(__FILE__) . "/logger.php");
require_once(dirname(__FILE__) . "/db.php");
require_once(dirname(__FILE__) . "/api.php");

class HandyRemoteIP {
  public $remoteurl = "";
  
  public function  __construct($remoteurl) {
    $this->remoteurl = $remoteurl;
  }

  public function getIP($sName) {
    return $this->callRemote("getip", array("name" => $sName));
  }

  public function updateIP($sName, $sPass, $sIP="") {
    return $this->callRemote("updateip", array("name" => $sName, "pass" => $sPass, "ip" => $sIP));
  }

  public function getMyIP() {
    return $this->callRemote("getmyip", array());
  }

  public function callRemote($action, $params=array()) {
    $aData = array(
      "a"       => $action
    );
    $aData = array_merge($aData, $params);
    $oResult = callRemote($this->remoteurl, $aData);
    if (is_null($oResult)) {
      throw new Exception(__METHOD__ . ": Null Error: " . serialize($oResult));
    }
    if (!isset($oResult->status)) {
      throw new Exception(__METHOD__ . ": Error: " . print_r($oResult,true));
    }
    if (!$oResult->status) {
      throw new Exception(__METHOD__ . ": Status failed: " . print_r($oResult,true));
    }
    return $oResult;
  }
}