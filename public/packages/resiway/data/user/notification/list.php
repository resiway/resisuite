<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce(	
	array(	
    'description'	=>	"Returns a user object",
    'params' 		=>	array(                                          
                        )
	)
);

list($result, $error_message_ids) = [[], []];


try {
    
    $om = &ObjectManager::getInstance();

    // retrieve current user    
    $user_id = ResiAPI::userId();
    if($user_id < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);


    $res = $om->read('resiway\User', $user_id, ['notifications_ids']);
    if($res < 0 || !isset($res[$user_id])) throw new Exception("request_failed", QN_ERROR_UNKNOWN_OBJECT);    
    

    $notifications = $om->read('resiway\UserNotification', $res[$user_id]['notifications_ids']);
    if($notifications > 0) {
        $result = array_values($notifications);
    }    
}
catch(Exception $e) {
    $result = $e->getCode();
    $error_message_ids = array($e->getMessage());
}

// send json result
header('Content-type: application/json; charset=UTF-8');
echo json_encode([
                    'result'            => $result, 
                    'error_message_ids' => $error_message_ids
                 ], 
                 JSON_PRETTY_PRINT);