<?php
/*
    This file is an adapter for autload of html\HtmlWrapper
*/
namespace {
    require_once(dirname(realpath(__FILE__)).'/HtmlWrapper/HtmlWrapper.class.php');
}
namespace html {
    // interface class for autoload
    class HtmlWrapper extends \HtmlWrapper {
        public function __construct($args=null) {
            parent::__construct($args);
        }
        
        public static function __callStatic($name, $arguments) {            
            return call_user_func_array("\HtmlWrapper::{$name}", $arguments);
        }        
    }
}