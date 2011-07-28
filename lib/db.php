<?php


class HandyDB {
  public $dbtype, $dbhost, $dbname, $dbuser, $dbpass, $dbconn, $bDebug;

  public function __construct($dbname, $dbuser="", $dbpass="", $dbtype="mysql", $dbhost="localhost", $bDebug=false) {
		$args = func_get_args();
		unset($args[2]);
    HandyLogger::debug(__METHOD__ . ": " . print_r($args,true));
    $this->dbname = $dbname;
    $this->dbhost = $dbhost;
    $this->dbtype = $dbtype;
    $this->dbuser = $dbuser;
    $this->dbpass = $dbpass;
    $this->dbconn = null;
    $this->bDebug = $bDebug;
  }

  public function startTransaction() {
    //TODO, using PDO?
  }

  public function delimitTable($sTable) {
    switch($this->dbtype) {
      case "mysql":
        return "`" . mysql_escape_string($sTable) . "`";
      default:
        throw new Exception("Unsupported type: " . $this->dbtpe);
    }
  }

  public function delimitFieldName($sField) {
    switch($this->dbtype) {
      case "mysql":
        return "`" . mysql_escape_string($sField) . "`";
      default:
        throw new Exception("Unsupported type: " . $this->dbtpe);
    }
  }

  public function escFieldValue($sValue) {
    switch($this->dbtype) {
      case "mysql":
        return mysql_escape_string($sValue);
      default:
        throw new Exception("Unsupported type: " . $this->dbtpe);
    }
  }

  public function existsTable($sTable) {

    $sql = "SELECT * FROM " . $this->delimitTable($sTable) . " LIMIT 1";
    try {
      $this->getDBQuery($sql);
    } catch (Exception $e) {
      return false;
    }
    return true;
  }
  /**
   * drop a table and execute a query
   *  normally used to relace table
   *  *caution: use with care, ensure sql is clean
   * @param <type> $sTable
   * @param <type> $sql
   */
  public function dropAndExecute($sTable, $sql) {
    dropTable($sTable);
    return $this->getDBQuery($sql);
  }

  public function dropTable($sTable) {
    switch($this->dbtype) {
      case "mysql":
        $sql = "DROP TABLE IF EXISTS " . $this->delimitTable($sTable);
        break;
      default:
        throw new Exception("Unsupported type: " . $this->dbtpe);
    }
    return $this->getDBQuery($sql);
  }

  public function dropView($sView) {
    switch($this->dbtype) {
      case "mysql":
        $sql = "DROP VIEW IF EXISTS " . $this->delimitTable($sView);
        break;
      default:
        throw new Exception("Unsupported type: " . $this->dbtpe);
    }
    return $this->getDBQuery($sql);
  }

  public function truncate($sTable) {
    switch($this->dbtype) {
      case "mysql":
        $sql = "TRUNCATE TABLE " . $this->delimitTable($sTable);
        break;
      default:
        throw new Exception("Unsupported type: " . $this->dbtpe);
    }
    
    return $this->getDBQuery($sql);
  }

  /**
   *
   * @param String $sTable, unescaped name
   * @param array $aFields, unescaped names and values
   * @param String $sWhere, escaped sql, makesure its clean
   * @return queryset
   */
  public function update($sTable, $aFields, $sWhere) {
    //array_combine
    //fields
    $sFields = "";
    foreach($aFields as $sField => $sValue) {
      $sFields .= empty($sFields) ? "" : ",";
      $sFields .= $this->delimitFieldName($sField) . "=";
      
      if (is_null($sValue)) { $sFields .="null"; } else {
        $sFields .="'" . $this->escFieldValue($sValue) . "'";
      }
    }
    $sql = "UPDATE " . $this->delimitTable($sTable) . " SET " . $sFields . " WHERE " . $sWhere;

    return $this->getDBQuery($sql);
  }

  /**
   *
   * @param String $sTable, unescaped name
   * @param array $aFields, unescaped names and values
   * @return queryset
   */
  public function insert($sTable, $aFields) {
    HandyLogger::debug(__METHOD__ . ": " . $sTable . ", " . print_r($aFields,true));
    //array_combine
    //fields
    $aCols = array_keys($aFields);
    $sFields = "";
    foreach($aCols as $sField) {
      $sFields .= empty($sFields) ? "" : ",";
      $sFields .= $this->delimitFieldName($sField);
    }
    //values
    $aCols = array_values($aFields);
    $sFieldValues = "";
    foreach($aCols as $sField) {
      $sFieldValues .= empty($sFieldValues) ? "" : ",";
      $sFieldValues .= "'" . $this->escFieldValue($sField) . "'";
    }
    $sql = "INSERT INTO " . $this->delimitTable($sTable) . " (" . $sFields . ") VALUES (" . $sFieldValues . ")";
    
    return $this->getDBQuery($sql);
  }
  
  function batchInsert($sTable, $aData) {
    if (count($aData) < 1) return;
    
    $aCols = array_keys($aData[0]);
    $sFields = "";
    foreach($aCols as $sField) {
      $sFields .= empty($sFields) ? "" : ",";
      $sFields .= $this->delimitFieldName($sField);
    }
    
    $sSQL = "";
    foreach($aData as $aFields) {
      $aCols = array_values($aFields);
      $sFieldValues = "";
      foreach($aCols as $sField) {
        $sFieldValues .= empty($sFieldValues) ? "" : ",";
        $sFieldValues .= "'" . $this->escFieldValue($sField) . "'";
      }
      $sSQL .= empty($sSQL) ? "": ",";
      $sSQL .= "(" . $sFieldValues . ")";
    }
    
    $sSQL = "INSERT INTO " . $this->delimitTable($sTable) . " (" . $sFields . ") VALUES " . $sSQL;
    $this->getDBQuery($sSQL);
  }
  
  function getLastId() {
    $oDB = $this->getDB();
    return mysql_insert_id($oDB);
  }

  function getDB() {
    if (is_null($this->dbconn)) {
      switch($this->dbtype) {
        case "mysql":
          HandyLogger::debug(__METHOD__ .": " . $this->dbhost . "," . $this->dbuser . "," . $this->dbpass);
          $this->dbconn = mysql_connect($this->dbhost, $this->dbuser, $this->dbpass);
          mysql_select_db($this->dbname, $this->dbconn);
          break;
        default:
          throw new Exception(__METHOD__ . ": Unknown database type: " . $this->dbtype);
      }
    }
    return $this->dbconn;
  }

  function getDBQuery($sql) {
    $oDB = $this->getDB();
    
    switch($this->dbtype) {
      case "mysql":
        HandyLogger::debug(__METHOD__ . ": " . $sql);
        if(!($query = mysql_query($sql,$oDB))) {
          throw new Exception(__METHOD__ . ": Query: " . $sql . ", error: " . mysql_error($oDB));
        }
        break;
    }
    return $query;
  }
}

?>
