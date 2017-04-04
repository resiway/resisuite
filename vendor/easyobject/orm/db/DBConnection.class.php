<?php
/*
 * KNINE php library
 *
 * DBConnection class
 *
 */
namespace easyobject\orm\db;
//function_exists('load_class') or die(__FILE__.' requires qn.lib.php');
// load_class('db/DBManipulatorMySQL');
// ... : add other DBMS manipulator classes files here

class DBConnection {

	private $dbConnection;

	private function __construct($host, $port, $name, $user, $password, $dbms) {
		switch($dbms) {
			case 'MYSQL' :
				$this->dbConnection = new DBManipulatorMySQL($host, $port, $name, $user, $password);
				break;
			/*
			// insert handling of other DBMS here
			case 'XYZ' :
				$this->dbConnection = new DBManipulatorXyz($host, $port, $name, $user, $password);
				break;
			*/
			default:
				$this->dbConnection = null;
		}
	}

	public function connect() {
		if(!isset($this->dbConnection)) return false;
		return $this->dbConnection->connect();
	}

	public function disconnect() {
		if(!isset($this->dbConnection)) return true;
		return $this->dbConnection->disconnect();
	}

	public static function &getInstance($host='', $port=0, $name='', $user='', $password='', $dbms = 'MYSQL')	{
		if (!isset($GLOBALS['DBConnection_instance'])) $GLOBALS['DBConnection_instance'] = new DBConnection($host, $port, $name, $user, $password, $dbms);
		return $GLOBALS['DBConnection_instance']->dbConnection;
	}
}