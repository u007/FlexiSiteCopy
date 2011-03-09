<?php

/**
 * include this file if you do not wish that any warning or notice
 *  to bypass your script
 * This function below becomes handy for good programming practice
 */
set_error_handler("_errorHandler");
function _errorHandler($errno, $errstr, $errfile, $errline)
{
  switch ($errno) {
    case E_ERROR:
      return false; //activate php error
      break;
    case E_USER_ERROR:
      return false; //activate php error
      break;
    case E_USER_WARNING:
      echo "<b>E_USER_WARNING</b>$errfile($errline) $errstr<br />\n";
      die();
      return true; //dont activate;
      break;
    case E_USER_NOTICE:
      echo "<b>E_USER_NOTICE</b>$errfile($errline) $errstr<br />\n";
      die();
      return true;
      break;
    case E_NOTICE:
      echo "<b>E_NOTICE</b>$errfile($errline) $errstr<br />\n";
      die();
      return true;
      break;
    case E_WARNING:
      echo "<b>E_WARNING</b>$errfile($errline) $errstr<br />\n";
      debug_print_backtrace();
      die();
      return true;
      break;
    case E_STRICT:
      echo "<b>E_STRICT</b>$errfile($errline) $errstr<br />\n";
      debug_print_backtrace();
      die();
      return true;
      break;
    default:
      //echo "unknown error:[$errno]$errfile($errline) $errstr<br />\n";
  }
  return false;
}

?>
