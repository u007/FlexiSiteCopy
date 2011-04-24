<?php

require_once "Net/Ping.php";

class PingURL {
  public $aPing = array();
  public $iSleep = 0.2; //to prevent detected as crawler
  
  public function __construct() {
    
  }

  public function showSummary($iMaxPing=null) {
    $aSummary = $this->getSummary($iMaxPing);
    foreach($aSummary as $oPing) {
      $sLine = "";
      foreach($oPing as $sKey=>$sValue) {
        $sLine .= empty($sLine) ? "": ", ";
        $sLine .= $sKey . ":" . $sValue . "";
      }
      $sLine .= "<br/>\n";
      echo $sLine;
    }
  }


  public function getSummary($iMaxPing=null) {
    $aResult = array();
    foreach($this->aPing as $sHost => $oPing) {

      if (!is_null($iMaxPing) &&
        isset($oPing["_round_trip"]) && $oPing["_round_trip"]["avg"] < $iMaxPing) {
        $aResult[] = array(
          "host" => $sHost,
          "min" => @$oPing["_round_trip"]["min"],
          "max" => @$oPing["_round_trip"]["max"],
          "avg" => @$oPing["_round_trip"]["avg"],
          "loss" => @$oPing["_loss"],
          "ip"   => $oPing["_target_ip"],
          "os"   => ($oPing["_sysname"]=="darwin" ? "unix/linux/darwin": $oPing["_sysname"])
        );
      }
    }//each ping result
    return $aResult;
  }

  public function pingURL($sURL, $iMaxDepth=0, $iDepth=0) {
    $aHost = parse_url($sURL);
    echo __METHOD__ . ": URL: " . $sURL . "<br/>\n";
    $sHost = !empty($aHost["host"])? $aHost["host"]: "";
    $sScheme = !empty($aHost["scheme"])? $aHost["scheme"]: "";
    $bPingOkay = false;
    if (!empty($sHost) && $sHost != "localhost") {
      if (!in_array($sHost, $this->aPing)) {
        $aResult = $this->doPing($sHost);
        if (!is_null($aResult)) {
          $bPingOkay = true;
          $this->aPing[$sHost] = $aResult;
        }
      }
    }// if host is not empty

    if ($iMaxDepth > $iDepth) {
      $sContent = file_get_contents($sURL);
      //$regex_pattern = "/<a href=\"(.*)\">(.*)<\/a>/";
      //$regex_pattern = '/<a[^>]+href="([^"]+)"[^"]*>/is';
      $regex_pattern = '/<a[^>]+href="([^"]+)"/i';
      preg_match_all($regex_pattern,$sContent,$matches);

      echo __METHOD__ . ": Child URL Count: " . count($matches[1]) . "<br/>\n";
      if (count($matches) > 0) {
        foreach($matches[1] as $sChildURL) {
          if ($this->iSleep >0.00) {
            sleep($this->iSleep);
          }
          $sChildURL = ltrim($sChildURL);
          //is not js and not ftp
          if (!empty($sChildURL)) {
            if (substr(strtolower($sChildURL),0,11) != "javascript:" && substr(strtolower($sChildURL),0,4) != "ftp:") {
              if (substr($sChildURL,0,4) != "http") {
                //append parent host
                if (!empty($sHost)) {
                  $sChildURL = $sScheme . "://" . $sHost . $sChildURL;
                  $this->pingURL($sChildURL, $iMaxDepth, $iDepth+1);
                } //else dont ping
              } else {
                //is proper http
                $this->pingURL($sChildURL, $iMaxDepth, $iDepth+1);
              }
            } //is not javascript
          }//is not empty
        }
      }
    } //ping okay

    return $bPingOkay;
  }

  public function doPing($sHost) {
    echo __METHOD__ . ": Host: " . $sHost . "<br/>\n";
    $ping = @Net_Ping::factory();
    
    if(@PEAR::isError($ping)) {
      echo $ping->getMessage();
      return null;
    }
    else
    {
      /* Number of packets to send */
      $ping->setArgs(array('count' => 4));
      $rawData = $ping->ping($sHost);
      return get_object_vars($rawData);
    }
  }

}
