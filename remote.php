<?php

ini_set("error_reporting", E_ALL & ~E_NOTICE);
require_once(dirname(__FILE__). "/lib/api.php");
doRequest();
exit();