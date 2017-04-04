<?php
/**
*    This file is part of the easyObject project.
*    http://www.cedricfrancoys.be/easyobject
*
*    Copyright (C) 2012  Cedric Francoys
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
* file: data/utils/last-change.php
*
* Returns the timestamp of the lastest change made in DB.
*
*/

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../qn.api.php');

// force silent mode (debug output would corrupt json data)
set_silent(true);

load_class('utils/DateFormatter');


// announce script and fetch parameters values
$params = announce(	
	array(	
		'description'	=>	"This script returns the timestamp of the latest modification made by a user to the current installation.",
		'params' 		=>	array(
							)
	)
);



// get singletons instances
$om = &ObjectManager::getInstance();
$db = &DBConnection::getInstance();

$result = $tables = $timestamps = array();
$packages = get_packages();

// load database tables
$res = $db->sendQuery("show tables;");
while($row = $db->fetchRow($res)) {
	$tables[$row[0]] = true;
}

// get last modif for each table
foreach($packages as $package) {
	$classes = get_classes($package);
	foreach($classes as $class) {
		$table = $om->getObjectTableName($package.'\\'.$class);
		if(isset($tables[$table])) {
			$res = $db->sendQuery("SELECT max(`modified`) as max FROM `$table`;");
			$row = $db->fetchRow($res);
			if(!is_null($row[0])) {
				$dateFormatter = new DateFormatter($row[0], DATE_TIME_SQL);
				$timestamps[] = $dateFormatter->getTimestamp();		
			}
		}
	}	
}
	
arsort($timestamps);
$result[] = array_shift($timestamps);

// send json result
header('Content-type: text/html; charset=UTF-8');
echo json_encode(array('result'=>$result));