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
* Include dependencies
*/

// load bootstrap library : system constants and functions definitions
include_once('../qn.lib.php');


// disable output
set_silent(false);


/**
* handle requests that do not match any script from the public filesystem
* the purpose of this script is to find a route matching the requested URL
*/

// get the base directory of the current script
$base = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')+1);

// retrieve original URI
$request_uri = $_SERVER['REQUEST_URI'];

// get a clean version of the request URI 
// removing everything after question mark, if any
if(($pos = strpos($request_uri, '?')) !== false) $request_uri = substr($request_uri, 0, $pos);
// removing everything after hash, if any
if(($pos = strpos($request_uri, '#')) !== false) $request_uri = substr($request_uri, 0, $pos);

// look for a match among defined routes
$params = [];
$uri = str_replace($base, '/', $request_uri);

$uri_parts = explode('/', ltrim($uri, '/'));
$found_url = null;

try {    
    // load routes definition
    if( ($json = file_get_contents('../config/routing.json')) === false) throw new Exception();    
    if( ($routes = json_decode($json)) == null) throw new Exception();
    // check routes and stop on first match

    foreach($routes as $route => $url) {

        $route_parts = explode('/', ltrim($route, '/'));
        // reset params
        $params = [];

        for($i = 0, $j = count($route_parts); $i < $j; ++$i) {
            $route_part = $route_parts[$i];
            $is_param = false;
            $is_mandatory = false;         
            if(strlen($route_part) && $route_part{0} == ':') {
                $is_param = true;
                $is_mandatory = !(substr($route_part, -1) == '?');
            }
            if($is_param) {
                if(isset($uri_parts[$i])) {
                    if($is_mandatory) $params[substr($route_part, 1)] = $uri_parts[$i];
                    else $params[substr($route_part, 1, -1)] = $uri_parts[$i];
                }
                else {
                    if($i == $j-1 && !$is_mandatory) $params[substr($route_part, 1, -1)] = '';
                    else continue 2;
                }
            }
            else if(!isset($uri_parts[$i]) || $route_part != $uri_parts[$i]) {
                continue 2;
            }
        }
        // we have a match
        $found_url = $url;
        break;
    }

} catch(Exception $e) {
    // unable to resolve given URI
    // todo : give some feedback about the error
}

if(!$found_url) {
	// set the header to HTTP 404 and exit
	header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
	header('Status: 404 Not Found');
	include_once('packages/core/html/page_not_found.html');    
}
// URL match found 
else {    
    // merge resolved params with URL params, if any
    $params = array_merge($params, config\QNlib::extract_params($found_url));
    // set the header to HTTP 200 and relay processing to index.php
    header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
    header('Status: 200 OK');
    // if found URL is another location    
    if($found_url[0] == '/') {
        // insert resolved params to pointed location, if any
        foreach($params as $param => $value) {
            $found_url = str_replace(':'.$param, $value, $found_url);
        }
        header('Location: '.$found_url);
    }
    else {
        // inject resolved params to global '$_REQUEST' (if a param is already set, its value is overwritten)    
        foreach($params as $key => $value) $_REQUEST[$key] = $value;        
        include_once('index.php');
    }
}