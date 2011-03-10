<?php

set_time_limit(500);
ini_set('memory_limit', '80M');
/**
 * Used by target remote calling curl (source)
 *  Copy remote site to deploy / staging server
 *  output format: json
 * @author James
 */
if (!defined("ENCRYPTION_KEY")) {
  define("ENCRYPTION_KEY", "SADFo92jzVs32j39IUYGvi6eL8v6");
}
if (! function_exists("json_encode")) {
  die("JSON module not installed");
}
if (! function_exists("mcrypt_decrypt")) {
  returnError("MCrypt module not installed");
  die();
}
if (! function_exists("gzcompress")) {
  returnError("GZip module not installed");
  die();
}

require_once(dirname(__FILE__) . "/errorhandler.php");

function doRequest() {
  global $action, $dbtype, $dbhost, $dbname, $dbuser, $dbpass, $oDBConn, $bDebug;

  $action = getReq("a");
  $dbtype = getReq("dbtype", "mysql");
  $dbhost = getReq("dbhost", "localhost");
  $dbname = getReq("dbname");
  $dbuser = getReq("dbuser");
  $dbpass = getReq("dbpass");
  $bDebug = getReq("debug", 0);
  $oDBConn = null;
  try {
    switch($action) {
      case "list":
        doList();
        break;
      case "getrows":
        doGetRows(getReq("table"), getReq("select", "*"), getReq("where",""), getReq("offset", 0), getReq("limit",""));
        break;
      case "getrow":
        doGetRow(getReq("table"), getReq("select", "*"), getReq("where",""));
        break;
      case "getcol":
        doGetCols(getReq("table"), getReq("select", "*"));
        break;
      case "getcreate":
        doGetCreate(getReq("table"), getReq("exclude", ""));
        break;
      case "getip":
        doGetIP(getReq("name"));
        break;
      case "updateip":
        doUpdateIP(getReq("name"), getReq("pass"), getReq("ip"));
        break;
      case "getmyip":
        doGetMyIP();
        break;
      default:
        throw new Exception("Unknown action: " . $action);
    }
  } catch (Exception $e) {
    return returnError($e->getMessage(), $e->getTraceAsString());
  }
  
}

function doGetMyIP() {
  return returnResult(getenv("REMOTE_ADDR"));
}

//======BEGIN ACTIONS=========

function doGetIP($sName) {
  $sName = preg_replace("/[^a-zA-Z0-9_\-]/", "", $sName);
  if (empty($sName)) {
    return returnError("Invalid namelength");
  }
  $sFile = dirname(dirname(__FILE__)) . "/ip/config." . $sName . ".php";

  if (!file_exists($sFile)) {
    return returnError("No ip available");
  }
  require($sFile);
  return returnResult($ip);
}

/**
 * update ip of a given name
 * @param String $sName
 */
function doUpdateIP($sName, $asPass, $asIP) {
  $sName = preg_replace("/[^a-zA-Z0-9_\-]/", "", $sName);
  if (empty($asPass) || empty($sName)) {
    return returnError("Invalid name / password / ip length");
  }
  $sFile = dirname(dirname(__FILE__)) . "/ip/config." . $sName . ".php";

  $sNewIP = empty($asIP) ? getenv("REMOTE_ADDR"): $asIP;
  if (file_exists($sFile)) {
    require($sFile);
    if ($password != $asPass) {
      return returnError("invalid password");
    }
  }

  $sConfig = getVariableInPHPCode(array(
    "name" => $sName,
    "password" => $asPass,
    "ip"    => $sNewIP
  ));
  file_put_contents($sFile, $sConfig);
  return returnResult($sName);
}

function getVariableInPHPCode($aParam) {
  $sResult = "<" . "?\n";
  foreach($aParam as $sKey => $sValue) {
    $sResult .= "$" . $sKey . " = \"" . str_replace("\"", "\\\"", $sValue) . "\";\n";
  }
  $sResult .= "?" . ">\n";
  return $sResult;
}

/**
 * Get create table sql
 *  return result as array of [table => "sql",...]
 * @param String $sTable, separated by "," or "*"
 * @param String $sExclude, separated by ","
 * @return n/a
 */
function doGetCreate($sTable, $sExclude="") {
  
  $aTable = array();
  
  if ($sTable == "*") {
    $aTable = getTableList();
  } else {
    $aTable = explode(",", $sTable);
  }

  $aExclude = array();
  $aResult = array();
  if (!empty($sExclude)) { $aExclude = explode(",", $sExclude); }

  foreach($aTable as $table) {
    if (! in_array($table, $aExclude)) {
      $aResult[$table] = getTableCreateSQL($table);
    }
  }

  return returnResult($aResult);
}

function doGetCols($sTable, $sCol = "*") {
  global $dbtype;
  switch ($dbtype) {
    case "mysql":
      $sql = "describe " . $sTable;
      break;
    default:
  }
  $result = getDBFetchAllAssoc($sql);
  return returnResult($result);
}

/**
 * Get datas return with 1st row as columns name,
 *  and 2nd onwards rows as data
 * @global String $dbtype
 * @param String $sTable
 * @param String $sCol, default: *
 * @param String $sWhere, default: ""
 * @param int $iOffset, default: 0
 * @param int $iLimit, default: null
 * @return <type>
 */
function doGetRows($sTable, $sCol = "*", $sWhere = "", $iOffset = 0, $iLimit=null) {
  global $dbtype;
  $sql = "";
  switch($dbtype) {
    case "mysql":
      $sql = "select " . $sCol . " from " . $sTable . 
        (empty($sWhere) ? "": " where " . $sWhere) .
        (empty($iLimit) ? "": " limit " . $iLimit) .
        " offset " . $iOffset;
      break;
  }

  $aRow = getDBFetchAll($sql, true);
  //==BEGIN ENCODE ALL VALUES==/
  //1st row is header, so skip
  for($c=1; $c < count($aRow); $c++) {
    for($col=0; $col < count($aRow[$c]); $col++) {
      $aRow[$c][$col] = utf8_encode($aRow[$c][$col]);
    }
  }
  //==END ENCODE ALL VALUES==/

  return returnResult($aRow);
}


function doGetRow($sTable, $sCol = "*", $sWhere = "") {
  global $dbtype;
  $sql = "";
  switch($dbtype) {
    case "mysql":
      $sql = "select " . $sCol . " from " . $sTable . 
      (empty($sWhere) ? "": " where " . $sWhere) .
      " limit 1";
      break;
  }
  
  $aRow = getDBFetchOne($sql);
  //==BEGIN ENCODE ALL VALUES==/
  for($col=0; $col < count($aRow); $col++) {
    $aRow[$col] = utf8_encode($aRow[$col]);
  }
  //==END ENCODE ALL VALUES==/

  return returnResult($aRow);
}

function doList() {
  $aList = getTableList();
  return returnResult($aList);
}


//======END ACTIONS=========


function encryptB64($text)
{
  //$bytesData =
  return trim(base64_encode(gzcompress(
    mcrypt_encrypt(MCRYPT_RIJNDAEL_256, ENCRYPTION_KEY, $text, MCRYPT_MODE_ECB,
            mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)
            )))
    );
}

function decryptB64($text)
{
  return trim(
    mcrypt_decrypt(MCRYPT_RIJNDAEL_256, ENCRYPTION_KEY, gzuncompress(base64_decode($text)), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))
    );
}

/**
 * Get Request value from querystring / post
 * @param String $sName
 * @param mixed $mDefault
 * @return mixed
 */
function getReq($sName, $mDefault = null) {
  //return isset($_REQUEST[$sName]) ? $_REQUEST[$sName]: $mDefault;
  static $aData = null;
  if (is_null($aData)) {
    if (!empty($_REQUEST["e"])) {
      parse_str(decryptB64($_REQUEST["e"]), $aData);
    } else {
      $aData = array();
    }
    //var_dump(decryptB64($_REQUEST["e"]));
    //print_r($aData, true);
    //die();
  }
  return isset($aData[$sName]) ? $aData[$sName] : $mDefault;
}

function returnResult($result, $msg = "") {
  return returnStatus(true, $result, $msg);
}

function returnError($msg, $trace=false) {
  return returnStatus(false, null, $msg, $trace);
}

function returnStatus($status, $result=null, $msg="", $trace=false) {
  global $bDebug;
  if ($bDebug) {
    echo encryptB64(json_encode(array(
      "status" => $status,
      "return" => $result,
      "msg"    => $msg,
      "trace" => $trace
    )));
  } else {
    echo encryptB64(json_encode(array(
      "status" => $status,
      "return" => $result,
      "msg"    => $msg
    )));
  }
  
  return;
}

function getDB() {
  global $oDBConn, $dbtype, $dbname, $dbhost, $dbuser, $dbpass;

  if (is_null($oDBConn)) {
    switch($dbtype) {
      case "mysql":
        $oDBConn = mysql_connect($dbhost, $dbuser, $dbpass);
        mysql_select_db($dbname, $oDBConn);
        break;
      default:
        throw new Exception(__METHOD__ . ": connection failed");
    }
    
  }
  return $oDBConn;
}

/**
 * Get rows of data
 * @param String $sql
 * @param boolean $bHeader: if set to true, 1st row will be column names
 */
function getDBFetchAll($sql, $bHeader=false) {
  $query = getDBQuery($sql);
  $aResult = array();
  
  if ($bHeader) {
    $recordset = mysql_fetch_assoc($query);
    if ($recordset === false) { return $aResult; } //no more record
    $aResult[] = array_keys($recordset);
    $aResult[] = array_values($recordset);
  }
  while( $recordset = mysql_fetch_row($query)) {
    $aResult[] = $recordset;
  }
  return $aResult;
}

function getDBFetchAllAssoc($sql) {
  $query = getDBQuery($sql);
  $aResult = array();
  while( $recordset = mysql_fetch_assoc($query)) {
    $aResult[] = $recordset;
  }
  return $aResult;
}

function getDBFetchOne($sql) {
  global $dbtype;
  
  $query = getDBQuery($sql);
  $recordset = null;
  switch($dbtype) {
    case "mysql":
      $recordset = mysql_fetch_row($query);
      break;
  }

  return $recordset;
}

function getDBFetchOneAssoc($sql) {
  global $dbtype;

  $query = getDBQuery($sql);
  $recordset = null;
  switch($dbtype) {
    case "mysql":
      $recordset = mysql_fetch_assoc($query);
      break;
  }

  return $recordset;
}

function getDBQuery($sql) {
  global $dbtype;
  $oDB = getDB();
  
  switch($dbtype) {
    case "mysql":
      if(!($query = mysql_query($sql,$oDB))) {
        throw new Exception(__METHOD__ . ": Query: " . $sql . ", error: " . mysql_error());
      }
      break;
  }
	return $query;
}

function getTableList() {
  $aList = getDBFetchAll("show tables");
  $aResult = array();
  foreach($aList as $oRow) {
    $aResult[] = $oRow[0];
  }
  return $aResult;
}

function getTableCreateSQL($sTable) {
  $result = getDBFetchOne("show create table " . $sTable);
  if ($result===false) { return null; }
  return $result[1];
}

function callRemote($sURL, $aaData=array(), $aaHeader=array()) {
  //$output = decryptB64(_callRemote($sURL, $aaData, $aaHeader));
  $output = decryptB64(_callRemote($sURL, $aaData, $aaHeader));
  return json_decode($output);
}

//=====BEGIN CLIENT LIB======
function _callRemote($sURL, $aaData=array(), $aaHeader=array()) {
  $aHeader = array_merge(array(
    "User-Agent: Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.3) Gecko/20100423 Ubuntu/10.04 (lucid) Firefox/3.6.3",
    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
    "Accept-Language:	en-us,en;",
    "Accept-Encoding:	",
    "Accept-Charset:	ISO-8859-1,utf-8;q=0.7,*;q=0.7",
    "Keep-Alive:	115",
    "Connection: keep-alive",
    "Content-type: application/x-www-form-urlencoded",
    "Referer:	",
    "If-Modified-Since:	" . date("D, d M Y G:i:00") . " GMT",
    "Cache-Control:	max-age=0"
  ), $aaHeader);

  $opts = array(
    'http'=>array(
      'method'=>"POST",
      'header'=> implode("\r\n", $aHeader),
      'content' => "e=" . urlencode(encryptB64(http_build_query($aaData)))
    )
  );
  //var_dump($sData);
  $context = stream_context_create($opts);
  $sContent = file_get_contents($sURL, false, $context);
  if ($sContent===false) {
    throw new Exception("CURL failed");
  }
  //HandyLogger::debug(__METHOD__ . ": " . $sContent);
  return $sContent;
}


