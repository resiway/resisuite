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
* file: data/utils/sql-schema.php
*
* Returns the sql schema of the specified package.
*
* @param string $class
*/

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../qn.api.php');

// force silent mode (debug output would corrupt json data)
set_silent(true);

$packages = get_packages();

// announce script and fetch parameters values
$params = announce(	
	array(	
		'description'	=>	"This script returns the sql schema of the specified package.",
		'params' 		=>	array(
								'package'	=> array(
													'description' => 'Package for which we want SQL schema.',
													'type' => 'string', 
													'selection' => array_combine(array_values($packages), array_values($packages)),
													'required'=> true
													)
							)
	)
);


$params['package'] = strtolower($params['package']);

$result = array();


$classes_list = get_classes($params['package']);

$types_associations = array(
	'boolean' 		=> 'tinyint(4)',
	'integer' 		=> 'int(11)',
	'float' 		=> 'decimal(10,2)',	
	'string' 		=> 'varchar(255)',
	'short_text' 	=> 'text',
	'text' 			=> 'mediumtext',
	'date' 			=> 'date',
	'time' 			=> 'time',
	'datetime' 		=> 'datetime',
	'timestamp' 	=> 'timestamp',
	'selection' 	=> 'varchar(50)',
	'binary' 		=> 'mediumblob',
	'many2one' 		=> 'int(11)'
);

$m2m_tables = array();


foreach($classes_list as $class) {
	// get the full class name
	$class_name = $params['package'].'\\'.$class;
	// get the SQL table name
	$table_name = get_object_table_name($class_name);	
	// get the schema
	$schema = get_object_schema($class_name);
    // get static instance
    $object = &get_object_static($class_name);
	// init result array
	$result[] = "CREATE TABLE IF NOT EXISTS `{$table_name}` (";
	
	foreach($schema as $field => $description) {
		if(in_array($description['type'], array_keys($types_associations))) {
			$type = $types_associations[$description['type']];
			if($field == 'id') $result[] = "`{$field}` {$type} NOT NULL AUTO_INCREMENT,";
			elseif(in_array($field, array('creator','modifier','published','deleted'))) $result[] = "`{$field}` {$type} NOT NULL DEFAULT '0',";
			else $result[] = "`{$field}` {$type},";
		}
		else if($description['type'] == 'function' && isset($description['store']) && $description['store']) {
			$type = $types_associations[$description['result_type']];
			$result[] = "`{$field}` {$type} DEFAULT NULL,";
		}
		else if($description['type'] == 'many2many') {
			if(!isset($m2m_tables[$description['rel_table']])) $m2m_tables[$description['rel_table']] = array($description['rel_foreign_key'], $description['rel_local_key']);
		}
	}
	$result[] = "PRIMARY KEY (`id`)";

    if(method_exists($object, 'getUnique')) {
        $list = $object::getUnique();
        foreach($list as $fields) {
            $result[] = ",\nUNIQUE KEY `{$fields[0]}` (`".implode('`,`', $fields)."`)";
        }
    
    }
    
	$result[] = ") DEFAULT CHARSET=utf8;\n";
}

foreach($m2m_tables as $table => $columns) {
	$result[] = "CREATE TABLE IF NOT EXISTS `{$table}` (";
	$key = '';
	foreach($columns as $column) {
		$result[] = "`{$column}` int(11) NOT NULL,";
		$key .= "`$column`,";
	}
	$key = rtrim($key, ",");
	$result[] = "PRIMARY KEY ({$key})";
	$result[] = ");\n";
	// add an empty records (mandatory for JOIN conditions on empty tables)
	$result[] = "INSERT INTO `{$table}` (".implode(',', array_map(function($col) {return "`{$col}`";}, $columns)).') VALUES ';
	$result[]= '('.implode(',', array_fill(0, count($columns), 0)).");\n";
}

// send json result
header('Content-type: text/html; charset=UTF-8');
echo json_encode(array('result' => $result, 'url' => '', 'error_message_ids' => ''));