<?php
/*
    This file is an adapter for autload of html\phpQuery
*/
namespace {
    require_once(dirname(realpath(__FILE__)).'/phpQuery/phpQuery.php');
}
namespace html {
    // interface class for autoload
    class phpQuery extends \phpQuery {
        public function __construct($args) {
            parent::__construct($args);
        }
        
        public static function __callStatic($name, $arguments) {            
            return call_user_func_array("\phpQuery::{$name}", $arguments);
        }        
    }
}