<?php

$sPath = dirname(dirname(__FILE__)) . "/ip/config.";

$sName = $_REQUEST["name"];
$sName = preg_replace("/[^a-zA-Z0-9_\-]/", "", $sName);
$sFile = $sPath . $sName . ".php";

if (file_exists($sFile)) {
  require_once($sFile);
  $sURL = "http://" . $ip;
  //you may alter url based on name here
  header("location: " . $sURL);
} else {
  die("No such ip by name: " . $sName);
}