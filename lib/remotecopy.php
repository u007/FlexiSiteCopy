<?php

require_once(dirname(__FILE__) . "/logger.php");
require_once(dirname(__FILE__) . "/db.php");
require_once(dirname(__FILE__) . "/api.php");

class HandyRemoteCopy {
  public $oLocalDB;
  public $remoteurl, $remotedb, $remotedbuser, $remotedbpass="", $remotedbhost;
  public $bDebug = true;
  public $aEvent = array();
  
  public function __construct(HandyDB $oLocalDB, $remoteurl, $remotedb, $remotedbuser, $remotedbpass="", $remotedbhost="localhost") {
    $this->oLocalDB = & $oLocalDB;
    $this->remoteurl = $remoteurl;
    $this->remotedb = $remotedb;
    $this->remotedbuser = $remotedbuser;
    $this->remotedbpass = $remotedbpass;
    $this->remotedbhost = $remotedbhost;
  }

  /**
   * Call user function on an event
   * @param String $sEventName, "my_function" or "myclass::myfunction"
   * @param <type> $sFunc
   */
  public function addEvent($sEventName, $mFunc) {
    if (!isset($this->aEvent[$sEventName])) {
      $this->aEvent[$sEventName] = array();
    }
    $callback = strpos($mFunc,":") !==false ? explode("::", $mFunc) : $mFunc;
    $this->aEvent[$sEventName][] = $callback;
  }
  
  public function doCopyAllTable($sExclude="") {
    $oLocalDB = &$this->oLocalDB;
    
    $oCreate = $this->callRemote("getcreate", array("table" => "*", "exclude" => $sExclude));
    if (! $oCreate->status) { throw new Exception(__METHOD__. ": create table failed"); }

    foreach($oCreate->return as $sTable => $sCreate) {
      HandyLogger::debug(__METHOD__ . ": " . $sCreate);
      $oLocalDB->dropTable($sTable);
      $oLocalDB->getDBQuery($sCreate);
      //only copydata if is table
      if (substr($sCreate,0,12) == "CREATE TABLE") {
        self::doCopyData($sTable, true);
      }
      HandyLogger::info(__METHOD__ . ": Done:" . $sTable);
    }
    HandyLogger::info(__METHOD__ . ": Done All.");
  }

  public function doCopyTable($sTable) {
    $oLocalDB = &$this->oLocalDB;
    
    $oCreate = $this->callRemote("getcreate", array("table" => $sTable));
    if (! $oCreate->status) { throw new Exception(__METHOD__. ": create table failed"); }
    $sCreate = $oCreate->return->$sTable;
    HandyLogger::debug(__METHOD__ . ": " . $sCreate);
    
    $oLocalDB->dropTable($sTable);
    $oLocalDB->getDBQuery($sCreate);
    self::doCopyData($sTable, true);

    HandyLogger::info(__METHOD__ . ": Done:" . $sTable);
  }

  /**
   * do Copy data,
   *  triggerable event: "save": ("insert/update", "tablename", array:fields), return void
   *  triggerable event: "before_copydata": ("tablename"), return true: continue, false: do not copy
   * @param String $sTable
   * @param boolean $bTruncate
   * @param int $iStart
   * @param int $iMax
   * @param int $iBufferRow
   */
  public function doCopyData($sTable, $bTruncate = false, $iStart=0, $iMax=null, $iBufferRow = 500) {
    $oLocalDB = &$this->oLocalDB;
    
    $aEvent = isset($this->aEvent["before_copydata"]) ? $this->aEvent["before_copydata"]: array();
    foreach($aEvent as $callback) {
      if (! call_user_func($callback, $sTable)) {
        return; //skipping data copy
      }
    }

    if ($bTruncate) $oLocalDB->truncate($sTable);
    $iLimit = $iBufferRow; $iOffset = $iStart; $iCnt = 0;
    
    $oData = $this->callRemote("getrows", array("table" => $sTable, "offset" => $iOffset, "limit" => $iLimit));
    $aHeader = $oData->return ? $oData->return[0]: array();
    
    while($oData->status && count($oData->return) > 1 && (is_null($iMax) || (!is_null($iMax) && $iCnt > $iMax))) {
      //loop throw record and limit it by iMax
      for($iRow=1; $iRow < count($oData->return) && (is_null($iMax) || (!is_null($iMax) && $iCnt > $iMax)); $iRow++) {
        $aRow = $oData->return[$iRow];
        //decode utf
        for($c=0; $c < count($aRow); $c++) {
          $aRow[$c] = utf8_decode($aRow[$c]);
        }
        //calling event hooks
        $aEvent = isset($this->aEvent["save"])? $this->aEvent["save"]: array();
        $aSaveData = array_combine($aHeader, $aRow);
        foreach($aEvent as $callback) {
          call_user_func_array($callback, array("insert", $sTable, &$aSaveData));
        }
        //if is null, do not insert
        if (! is_null($aSaveData)) {
          $oLocalDB->insert($sTable, $aSaveData);
        }
        $iCnt++;
      }
      $iOffset+= $iLimit;
      //smart break: return record+1 header
      if (count($oData->return) < $iLimit+1) { break; }
      $oData = $this->callRemote("getrows", array("table" => $sTable, "offset" => $iOffset, "limit" => $iLimit));
    }
    if (! $oData->status) { throw new Exception(__METHOD__. ": data failed, " . print_r($oData, true)); }
  }

  public function callRemote($action, $params=array()) {
    $aData = array(
      "a"       => $action,
      "dbname"  => $this->remotedb,
      "dbuser"  => $this->remotedbuser,
      "dbpass"  => $this->remotedbpass,
      "debug"   => $this->bDebug ? 1: 0
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

?>
