<?php

$sPath = dirname(dirname(__FILE__)) . "/ip/config.";

$sName = $_REQUEST["name"];
$sName = preg_replace("/[^a-zA-Z0-9_\-]/", "", $sName);
$sAlias = $sName;

if (file_exists(dirname(__FILE__)."/plugin.php"))  require_once dirname(__FILE__).'/plugin.php';
//getconfig file, return alias if possible
if (function_exists("onGetAlias")) {
  $sAlias = onGetAlias($sName);
}

$sFile = $sPath . $sAlias . ".php";
if (file_exists($sFile)) {
  require_once($sFile);
  $sURL = "http://" . $ip;

  if (function_exists("onGetURL")) {
    onGetURL($sName, $ip, $sURL);
  }
  //you may alter url based on name here
  header("location: " . $sURL);
} else {
  die("No such ip by name: " . $sName);
}