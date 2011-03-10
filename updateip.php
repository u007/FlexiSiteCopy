<?php


//optional encryption key, to ensure security
//define("ENCRYPTION_KEY", "MYMODXYOISLDHFLHKUDGKFIDCBJ");
require_once(dirname(__FILE__) . "/lib/remoteip.php");

echo "<br/>\n===BEGIN [SITENAME] IP====<br/>\n";

$remoteurl = "http://www.wire-network.com/vo/internal/utils/copysite/remote.php";
$remoting = new HandyRemoteIP($remoteurl);
$aResult = $remoting->updateip("[any_name_to_identify_ip]", "[password_to_prevent_other_update]", "[optional_ip_may_be_blank_to_auto_detect_caller_ip]");
if (!$aResult->status) {
  exit(1);
}

$aReturn = $remoting->getIP("lan");
//echo "updated ip: " . print_r($aReturn,true) . "\n";
echo "New ip: " . $aReturn->return . "\n";


//$aMyIP = $remoting->getMyIP();
//echo "My ip: " . $aMyIP->return . "\n";


echo "<br/>\n===END [SITENAME] IP====<br/>\n";

?>