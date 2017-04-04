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
    'description'	=>	"Remove a notification",
    'params' 		=>	[
        'notification_id'	=> array(
                            'description'   => 'Identifier of the notification being removed.',
                            'type'          => 'integer',
                            'required'      => true
                            )
                       
    ]
]);


list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class, $object_id) = [ 
    'resiway_notification_dismiss',
    'resiway\UserNotification',
    $params['notification_id']
];


try {
    $om = &ObjectManager::getInstance();
    $res = $om->remove($object_class, $object_id, true);
    if($res < 0 || !count($res)) throw new Exception("action_failed", QN_ERROR_UNKNOWN); 
    
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