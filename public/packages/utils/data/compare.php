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
* file: data/utils/compare.php
*
* Returns the timestamp of the lastest change made in DB.
*
*/

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../qn.api.php');

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = announce(	
	array(	
		'description'	=>	"This script tells if two easyObject databases are synchronized.",
		'params' 		=>	array(
								'url1'	=> array(
													'description' => 'Base URL of the first easyObject installation.',
													'type' => 'string', 
													'required'=> true
													),
								'url2'	=> array(
													'description' => 'Base URL of the second easyObject installation.',
													'type' => 'string', 
													'required'=> true
													)
							)
	)
);

load_class('utils/HttpRequest');

$request = new HttpRequest($params['url1'].'?get=utils_last-change');
$url1 = json_decode($request->get(), true);

$request = new HttpRequest($params['url2'].'?get=utils_last-change');
$url2 = json_decode($request->get(), true);

$result = array();
$result[] = (bool) (isset($url2['result'][0]) && isset($url2['result'][0]) && ($url1['result'][0] == $url2['result'][0]));

// send json result
header('Content-type: text/html; charset=UTF-8');
echo json_encode(array('result' => $result, 'url' => '', 'error_message_ids' => ''));