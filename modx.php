<?php

//optional encryption key, to ensure security
//define("ENCRYPTION_KEY", "MYMODXYOISLDHFLHKUDGKFIDCBJ");

require_once(dirname(__FILE__) . "/lib/remotecopy.php");

echo "<br/>\n===BEGIN [SITENAME] MODX2====<br/>\n";
$iTimeStart = time();
$remoteurl = "http://[remoteorsource-site-url-to-copy-from]/[path-to]/remote.php";
//connect to localdb
$localdb = new HandyDB("[local-db-name]", "[local-db-user]", "[local-db-password]", "mysql", "127.0.0.1", true);
//connect to remote api
$remoting = new HandyRemoteCopy($localdb, $remoteurl, "[remoteorsource-db-name]", "[remoteorsource-db-user]", "[remoteorsource-db-password]");

//these 2 event is optional
$remoting->addEvent("save", "onModx2Save");
$remoting->addEvent("before_copydata", "onModx2CopyData");

//tablename, separated by comma, empty for all table
$remoting->doCopyAllTable("");

echo "<br/>\nDuration: " . (time()-$iTimeStart). "s<br/>\n";
echo "<br/>\n===END [SITENAME] MODX2====<br/>\n";

/**
* Example function to copy data of table / not
* @param String $sTable
* @boolean true: to copy, false: otherwise
*/
function onModx2CopyData($sTable) {
  //skipping some tables data
  switch(strtolower($sTable)) {
    case "modx_event_log":
    case "modx_manager_log":
    case "modx_session":
      HandyLogger::info(__METHOD__ . ": Skipping data: " . $sTable);
      return false;
      break;
    default:
  }
  return true;
}

/**
* Row event to alter row value before actual insert
* 	To skip row, set $aFields = null
* @param String $sType - insert / update (only insert available)
* @param String $sTable
* @param array Row values in a hash array
*/
function onModx2Save($sType, $sTable, & $aFields) {
  if (is_null($aFields)) { return; } //null due to other event already cancel the insert/update
  
  switch(strtolower($sTable)) {
    case "modx_workspaces":
      //replace path
      if ($aFields["id"] == 1) {
        HandyLogger::info(__METHOD__ . ": Replacing path");
        $aFields["path"] = "[full-path-to-your-modx-core:example:/var/www/example.com/core/";
      }
      break;
    case "modx_system_settings":
      if ($aFields["key"] == "cookiefile") {
        HandyLogger::info(__METHOD__ . ": Skipping setting of key=cookiefile");
        $aFields = null; //skip this row
      }
      break;

    default:
  }
}

?>