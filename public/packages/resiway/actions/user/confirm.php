<?php
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');

use config\QNlib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;

require_once('../resi.api.php');

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce(	
	array(	
    'description'	=>	"Attempt to register a new user.",
    'params' 		=>	array(
                        'code'	    =>  array(
                                        'description'   => 'unique identification code sent to the user.',
                                        'type'          => 'string', 
                                        'required'      => true
                                        )
                        )
	)
);

list($result, $error_message_ids) = [true, []];
list($code) = [$params['code']];

try {
    list($login, $password) = ResiAPI::credentialsDecode($code);
    
    $user_id = ResiAPI::userSign($login, $password);
    
    if($user_id < 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);    
    
    // update 'verified' field
    $om = &ObjectManager::getInstance();    
    $om->write('resiway\User', $user_id, [ 'verified' => 1 ]);
    
    // update badges
    ResiAPI::updateBadges(
        'resiway_user_confirm',
        'resiway\User',
        $user_id
    );      
}
catch(Exception $e) {
    $error_message_ids = array($e->getMessage());
    $result = $e->getCode();
}

// send json result
header('Content-type: application/json; charset=UTF-8');
echo json_encode([
                    'result'            => $result, 
                    'error_message_ids' => $error_message_ids
                 ], JSON_PRETTY_PRINT);
