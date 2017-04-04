<?php
namespace easyobject\orm;


class PersistentDataManager {

	private $valuesTable;

	private function __construct($valuesTable=[]) {
		$this->valuesTable = $valuesTable;
	}

	public static function &getInstance()	{
		if (!isset($GLOBALS['PersistentDataManager_instance'])) {
			$valuesTable = array();
            if(isset($_SESSION['PersistentDataManager_instance'])) {
                // we need to restore object this way because at the time we do so, class might not be fully loaded
                $incomplete_object = unserialize($_SESSION['PersistentDataManager_instance']);
                $valuesTable = $incomplete_object->valuesTable;
            }            
			$GLOBALS['PersistentDataManager_instance'] = new PersistentDataManager($valuesTable);
		}
		return $GLOBALS['PersistentDataManager_instance'];
	}

	public function __destruct() {
		// to keep track of users data, we store them in the SESSION global array
		$_SESSION['PersistentDataManager_instance'] = serialize($this);
	}

	public function __sleep() {
		// we need to store valuesTable into session array
		return array('valuesTable');
	}

    public function reset() {
        $this->valuesTable = array();
    }
    
    public function delete($key) {
        unset($this->valuesTable[$key]);
    }
    
	public function get($key, $default=null) {
        if(!isset($this->valuesTable[$key])) return $default;
    	return $this->valuesTable[$key];
	}

	public function set($key, $value) {
		$this->valuesTable[$key] = $value;
		return $value;
	}

}
