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

/**
* Dispatcher's role is to set up the context and handle the client calls
*
*/

/**
* Include dependencies
*/

// load bootstrap library : system constants and functions definitions
/*
    QN library allows to include required files and classes	
*/
include_once('../qn.lib.php');

// 3) load current user settings
// try to start or resume the session
if(!strlen(session_id())) session_start() or die(__FILE__.', line '.__LINE__.", unable to start session.");


/**
* Define context
*/

// prevent vars initialization from generating output
set_silent(true);


// todo : to remove or add to fc.lib
// get the base directory of the current script (easyObject installation directory being considered as root for URL redirection)
define('BASE_DIR', substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')+1));


// set the languages in which UI and content must be displayed

// UI items : UI language is the one defined in the user's settings (core/User object)
// todo : remove UI stuff from server
//isset($_SESSION['LANG_UI']) or $_SESSION['LANG_UI'] = user_lang();

// Content items :
//		- for unidentified users, language is DEFAULT_LANG
//		- for identified users language is the one defined in the session
//		- if a parameter lang is defined in the HTTP request, it overrides user's language
isset($_SESSION['LANG']) or $_SESSION['LANG'] = DEFAULT_LANG;
$params = config\QNlib::get_params(array('lang'=>$_SESSION['LANG']));
$_SESSION['LANG'] = $params['lang'];

// from now on, we let the requested script decide whether or not to output error messages if any
set_silent(false);

// we need to prevent double escaping (especially for class names)
if (get_magic_quotes_gpc()) {
	function stripslashes_deep($value) {
		return is_array($value) ?  array_map('stripslashes_deep', $value) : stripslashes($value);
	}
	$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

// We need the whole $_FILES array
// So we merge superglobal arrays $_FILES and $_REQUEST (in order to let the manager handle binary fields)
$_REQUEST = array_merge($_REQUEST, $_FILES);

/**
* Dispatching : include the requested script
*/
$accepted_requests = array(
						'do'	=> array('type' => 'action handler','dir' => 'actions'), 	// do something server-side
						'get'	=> array('type' => 'data provider',	'dir' => 'data'),		// return some data (json)
						'show'	=> array('type' => 'application',	'dir' => 'apps')		// output rendering information (html/js)
					);


// check current request for package specification
foreach($accepted_requests as $request_key => $request_conf) {
	if(isset($_REQUEST[$request_key])) {
		$parts = explode('_', $_REQUEST[$request_key]);
		$package = array_shift($parts);
		config\define('DEFAULT_PACKAGE', $package);	
		break;
	}
}

// if no package is pecified in the URL, check for DEFAULT_PACKAGE constant (defined in root config.inc.php)
if(!config\defined('DEFAULT_PACKAGE') && defined('DEFAULT_PACKAGE')) config\define('DEFAULT_PACKAGE', DEFAULT_PACKAGE);

if(config\defined('DEFAULT_PACKAGE')) {
	// if package has a custom configuration file, load it
	if(is_file('packages/'.config\config('DEFAULT_PACKAGE').'/config.inc.php')) include('packages/'.config\config('DEFAULT_PACKAGE').'/config.inc.php');
}

// if no request is specified, set DEFAULT_PACKAGE/DEFAULT_APP as requested script
if(count(array_intersect_key($accepted_requests, $_REQUEST)) == 0) {
	if(config\defined('DEFAULT_PACKAGE') && config\defined('DEFAULT_APP')) {
        $_REQUEST['show'] = config\config('DEFAULT_PACKAGE').'_'.config\config('DEFAULT_APP');
    }
}

// try to include requested script
foreach($accepted_requests as $request_key => $request_conf) {
	if(isset($_REQUEST[$request_key])) {
		$parts = explode('_', $_REQUEST[$request_key]);
		$package = array_shift($parts);
		// if no app is specified, use the default app (if any)
		if(empty($parts) && config\defined('DEFAULT_APP')) $parts[] = config\config('DEFAULT_APP');
		$filename = 'packages/'.$package.'/'.$request_conf['dir'].'/'.implode('/', $parts).'.php';
		is_file($filename) or die ("'{$_REQUEST[$request_key]}' is not a valid {$request_conf['type']}.");
		// export as constants all parameters declared with config\define() to make them accessible through global scope
		config\export_config();

		// include and execute requested script
		include($filename);
		break;
	}
}