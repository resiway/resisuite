<?php
namespace {
    require_once 'swiftmailer/lib/swift_required.php';
}
namespace mail {
    // interface class for autoload
    class Swift_SmtpTransport extends \Swift_SmtpTransport {
        
        public function __construct($args) {
            parent::__construct($args);
        }
        
        public static function __callStatic($name, $arguments) {            
            return call_user_func_array("\Swift_SmtpTransport::{$name}", $arguments);
        }        
    }
    
}    