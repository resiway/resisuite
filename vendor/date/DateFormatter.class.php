<?php
namespace date;
use \DateTime as DateTime;

/*
Note :
Constants already defined in the DateTime php class

	DATE_ATOM			Y-m-d\TH:i:sP		2005-08-15T15:52:01+00:00
	DATE_COOKIE			l, d-M-y H:i:s T	Monday, 15-Aug-05 15:52:01 UTC
	DATE_ISO8601		Y-m-d\TH:i:sO		2005-08-15T15:52:01+0000
	DATE_RFC822			D, d M y H:i:s O	Mon, 15 Aug 05 15:52:01 +0000
	DATE_RFC850			l, d-M-y H:i:s T	Monday, 15-Aug-05 15:52:01 UTC
	DATE_RFC1036		D, d M y H:i:s O	Mon, 15 Aug 05 15:52:01 +0000
	DATE_RFC1123		D, d M Y H:i:s O	Mon, 15 Aug 2005 15:52:01 +0000
	DATE_RFC2822		D, d M Y H:i:s O	Mon, 15 Aug 2005 15:52:01 +0000
	DATE_RFC3339		Y-m-d\TH:i:sP		2005-08-15T15:52:01+00:00
	DATE_RSS			D, d M Y H:i:s O	Mon, 15 Aug 2005 15:52:01 +0000
	DATE_W3C			Y-m-d\TH:i:sP		2005-08-15T15:52:01+00:00
*/

define('DATE_UNIX',				'F j Y H:i:s T');		// unix notation based on elapsed seconds since the Unix Epoch
define('DATE_INDEX',			'Ymd');					// date as sortable unique index
define('DATE_SQL',				'Y-m-d');				// sql date format, same as ISO8601 notation
define('DATE_TIME_SQL',			'Y-m-d H:i:s');			// sql datetime format
define('DATE_LITTLE_ENDIAN',	'd/m/Y');				// common non-US notation
define('DATE_MIDDLE_ENDIAN',	'm/d/Y');				// common US notation
define('DATE_ARRAY',			'date_array');			// array notation : {'year'=>year,'month'=>month,'day'=>day}
define('DATE_STRING',			'date_string');			// string variable notation : yy/mm/dd or or yyyy/m/d or or yyyy/m/dd or yyyy/mm/d OR mm/dd/yyyy or m/d/yyyy or m/dd/yyyy or mm/d/yyyy OR dd/mm/yyyy or d/m/yyyy or d/mm/yyyy or dd/m/yyyy


class DateFormatter {
	private $dateTime;

	const UNIX			= DATE_UNIX;
	const INDEX			= DATE_INDEX;
	const SQL			= DATE_SQL;
	const LITTLE_ENDIAN	= DATE_LITTLE_ENDIAN;
	const MIDDLE_ENDIAN	= DATE_MIDDLE_ENDIAN;

	const ARRAY_FORMAT	= DATE_ARRAY;
	const STRING_FORMAT	= DATE_STRING;

    private static $separators = " -/.";

	private static $days_names		= array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	private static $months_names	= array(1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

	// ISO-8601 and US days orders
	private static $days_order	= array('iso' => array('1','2','3','4','5','6','0'), 'us' => array('0','1','2','3','4','5','6'));


	public function __construct($date='', $format=self::UNIX) {
		// instanciate the inner DateTime object
		$this->dateTime = new DateTime();
		// by default, set to current date with UNIX format
		if(empty($date)) $date = date($format);		
		$this->setDate($date, $format);
	}

	public function getTimestamp() {
		return $this->dateTime->getTimestamp();
	}
	
	// in case of doubt (ex. 01/01/2012), little endian format will be chosen
	private function checkStringDate($string_date) {
		// little endian : dd/mm/yyyy or d/m/yyyy or d/mm/yyyy or dd/m/yyyy
		if(preg_match("/^(0?[1-9]|[12][0-9]|3[01])[- \/\.](0?[1-9]|1[012])[- \/\.]((19|20)[0-9]{2})$/", $string_date) == 0) {
			// middle endian : mm/dd/yyyy or m/d/yyyy or m/dd/yyyy or mm/d/yyyy
			if(preg_match("/^(0?[1-9]|1[012])[- \/\.](0?[1-9]|[12][0-9]|3[01])[- \/\.]((19|20)[0-9]{2})$/", $string_date) == 0) {
				// big endian : yyyyy/mm/dd or yyyy/m/d or or yyyy/m/dd or yyyy/mm/d
				if(preg_match("/^((19|20)[0-9]{2})[- \/\.](0?[1-9]|1[012])[- \/\.](0?[1-9]|[12][0-9]|3[01])$/", $string_date) == 0) {
					return 0;
				}
				return 3;
			}
			return 2;
		}
		return 1;
	}

	public function setDate($date, $format) {
		if($format == self::STRING_FORMAT) {
			$format = self::ARRAY_FORMAT;
			$values = array();
			$token = strtok($date, self::$separators);
			while($token !== false) {
				$values[] = $token;
				$token = strtok(self::$separators);
			}
			switch($this->checkStringDate($date)) {
				case 1:
					// little endian : dd/mm/yyyy or d/m/yyyy or d/mm/yyyy or dd/m/yyyy
					$date = array();
					$date['day']	= $values[0];
					$date['month']	= $values[1];
					$date['year']	= $values[2];
					break;
				case 2:
					// middle endian : mm/dd/yyyy or m/d/yyyy or m/dd/yyyy or mm/d/yyyy
					$date = array();
					$date['day']	= $values[1];
					$date['month']	= $values[0];
					$date['year']	= $values[2];
					break;
				case 3:
					// big endian : yyyyy/mm/dd or yyyy/m/d or or yyyy/m/dd or yyyy/mm/d
					$date = array();
					$date['day']	= $values[2];
					$date['month']	= $values[1];
					$date['year']	= $values[0];
					break;
			}
		}

		if($format == self::ARRAY_FORMAT) {
			if(is_array($date) && isset($date['year']) && isset($date['month']) && isset($date['day'])) {
				$this->dateTime->setDate($date['year'], $date['month'], $date['day']);
			}
		}
		else {
			$this->dateTime = DateTime::createFromFormat($format, $date);
		}
	}

	public function getDate($format) {
		if($format == self::STRING_FORMAT) $format = self::LITTLE_ENDIAN;
		return date($format, $this->dateTime->getTimestamp());
	}

	public function getDay() {
		return date('d', $this->dateTime->getTimestamp());
	}

	public function getMonth() {
		return date('m', $this->dateTime->getTimestamp());
	}

	public function getYear() {
		return date('Y', $this->dateTime->getTimestamp());
	}

	public static function getMonthsNames() {
		return self::$months_names;
	}

	public static function getDaysNames($order='iso') {
		$days_names = array();
		foreach(self::$days_order[$order] as $index) {
        	$days_names[] = self::$days_names[$index];
		}
		return $days_names;
	}
}