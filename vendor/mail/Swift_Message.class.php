<?php
namespace {
    require_once 'swiftmailer/lib/swift_required.php';
}
namespace mail {
    // interface class for autoload
    class Swift_Message extends \Swift_Message {
        
        public function __construct($args) {
            parent::__construct($args);
        }
        
        public static function __callStatic($name, $arguments) {            
            return call_user_func_array("\Swift_Message::{$name}", $arguments);
        }        
    }
    
}    