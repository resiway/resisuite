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
                        'id'	        => array(
                                            'description' => 'Identifier of the user to retrieve.',
                                            'type' => 'integer', 
                                            'required'=> true
                                            ),                                            
                        )
	)
);

list($object_class, $object_id) = ['resiway\User', $params['id']];

list($result, $error_message_ids) = [true, []];


/**
* note: for performance reasons, this script should not be requested for views involving users listing
*/
try {
    
    $om = &ObjectManager::getInstance();

    $user_id = ResiAPI::userId();
    if($user_id < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
    
    // retrieve given user data 
    // user and admins have acess to all fields
    if(ResiAPI::isActionAllowed($user_id, ResiAPI::actionId('resiway_user_edit'), $object_class, $object_id) ) {
        $user = ResiAPI::loadUserPrivate($object_id);
    }
    else {
        $user = ResiAPI::loadUserPublic($object_id);
    }
    
    if($user < 0) throw new Exception("user_unknown", QN_ERROR_UNKNOWN_OBJECT);
    
    // retrieve notifications
    $user['notifications'] = [];
    if(isset($user['notifications_ids'])) {
        $notifications = $om->read('resiway\UserNotification', $user['notifications_ids']);
        if($notifications > 0) {
            $user['notifications'] = array_values($notifications);
        }
    }
    
    $result = $user;
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