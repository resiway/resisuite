<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce([
    'description'	=>	"Remove current user's all notifications",
    'params' 		=>	[                       
    ]
]);


list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class) = [ 
    'resiway_notification_dismiss',
    'resiway\UserNotification'
];


try {
    $om = &ObjectManager::getInstance();

    // retrieve current user    
    $user_id = ResiAPI::userId();
    if($user_id < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
    
    $res = $om->read('resiway\User', $user_id, ['notifications_ids']);
    if($res < 0 || !isset($res[$user_id])) throw new Exception("request_failed", QN_ERROR_UNKNOWN_OBJECT);    
    
    if(count($res[$user_id]['notifications_ids'])) {
        $res = $om->remove($object_class, $res[$user_id]['notifications_ids'], true);
        if($res < 0 || !count($res)) throw new Exception("action_failed", QN_ERROR_UNKNOWN); 
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