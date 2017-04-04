<?php
/**
* Qinoa API
*
* Stand-alone functions defintions 
* Note: We export those methods to the global scope in order to relieve the user from the scope resolution and namespace notation.
*/

use easyobject\orm\ObjectManager as ObjectManager;
use easyobject\orm\AccessController as AccessController;
use easyobject\orm\I18n as I18n;

function announce($announcement) {
    return config\QNlib::announce($announcement);
}

function extract_params($url) {
    return config\QNlib::extract_params($url);
}

// deprecated : use announce method instead
function check_params($params) {
    config\QNlib::check_params($params);
}

// deprecated : use announce method instead
function get_params($params) {
    return config\QNlib::get_params($params);
}

function &get_object_static($class_name) {
    $om = &ObjectManager::getInstance();
    return $om->getStatic($class_name);
}

function get_object_schema($class_name) {
    $om = &ObjectManager::getInstance();
    return $om->getObjectSchema($class_name);
}

function get_object_package_name($class_name) {
    return ObjectManager::getObjectPackageName($class_name);
}

function get_object_table_name($class_name) {
    $om = &ObjectManager::getInstance();
    return $om->getObjectTableName($class_name);
}

function get_object_name($class_name) {
    return ObjectManager::getObjectName($class_name);    
}


function get_packages() {
    $packages_list = array();
    $package_directory = getcwd().'/packages';
    if(is_dir($package_directory) && ($list = scandir($package_directory))) {
        foreach($list as $node) if(is_dir($package_directory.'/'.$node) && !in_array($node, array('.', '..'))) $packages_list[] = $node;
    }
    return $packages_list;
}

function get_classes($package_name) {
    $classes_list = array();
    $package_directory = getcwd().'/packages/'.$package_name.'/classes';
    if(is_dir($package_directory) && ($list = scandir($package_directory))) {
        foreach($list as $node) if (stristr($node, '.class.php') && is_file($package_directory.'/'.$node)) $classes_list[] = substr($node, 0, -10);
    }
    return $classes_list;
}

/**
* API : stand-alone functions related to object management
* for full description, refer to methods in ObjectManager class
*/

function user_id() {
    $ac = &AccessController::getInstance();
    return $ac->user_id(session_id());
}

function user_key() {
    $ac = &AccessController::getInstance();
    return $ac->user_key(session_id());
}

function user_lang() {
    $ac = &AccessController::getInstance();
    return $ac->user_lang(session_id());
}

function login($login, $password) {
    $ac = &AccessController::getInstance();
    return $ac->login(session_id(), $login, $password);
}

/* load current user locale
*/
function load_user_locale($export=true) {
    I18n::loadLocale(user_lang());
    // export constants from user-specific locale
    if($export) config\export_config();
}

function validate($object_class, &$values) {
    $om = &ObjectManager::getInstance();
    return $om->validate($object_class, $values);
}

function &get($object_class, $object_id) {
    $om = &ObjectManager::getInstance();
    return $om->get($object_class, $object_id);
}

function create($object_class, $fields=NULL, $lang=NULL) { 
    $om = &ObjectManager::getInstance();
    $ac = &AccessController::getInstance();
    $uid = $ac->user_id(session_id());    
    if(!AccessController::hasRight($uid, $object_class, 0, R_CREATE)) return NOT_ALLOWED;
    return $om->create($object_class, $fields, (!$lang)?user_lang():$lang);
}

function read($object_class, $ids, $fields=NULL, $lang=NULL) {
    $om = &ObjectManager::getInstance();
    $ac = &AccessController::getInstance();
    $uid = $ac->user_id(session_id());
	if(!AccessController::hasRight($uid, $object_class, $ids, R_READ)) return NOT_ALLOWED;
    return $om->read($object_class, $ids, $fields, (!$lang)?user_lang():$lang);
}

function write($object_class, $ids, $values=NULL, $lang=NULL) {
    $om = &ObjectManager::getInstance();
    $ac = &AccessController::getInstance();
    $uid = $ac->user_id(session_id());    
    if(!AccessController::hasRight($uid, $object_class, $ids, R_WRITE)) return NOT_ALLOWED;
    return $om->write($object_class, $ids, $values, (!$lang)?user_lang():$lang);
}

function search($object_class, $domain=NULL, $order='id', $sort='asc', $start='0', $limit='', $lang=NULL) {
    $om = &ObjectManager::getInstance();
    $ac = &AccessController::getInstance();
    $uid = $ac->user_id(session_id());  
    if(!AccessController::hasRight($uid, $object_class, array(0), R_READ)) return NOT_ALLOWED;
    return $om->search($object_class, $domain, $order, $sort, $start, $limit, (!$lang)?user_lang():$lang);
}

function remove($object_class, $ids, $permanent=false) {   
    $om = &ObjectManager::getInstance();
    $ac = &AccessController::getInstance();
    $uid = $ac->user_id(session_id()); 
    if(!AccessController::hasRight($uid, $object_class, $ids, R_DELETE)) return NOT_ALLOWED;
    return $om->remove($object_class, $ids, $permanent);
}