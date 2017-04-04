<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;
use easyobject\orm\PersistentDataManager as PersistentDataManager;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce(	
	array(	
    'description'	=>	"Returns current user's pending notifications",
    'params' 		=>	array(                                          
                        )
	)
);

list($result, $error_message_ids) = [[], []];


try {    

    $pdm = &PersistentDataManager::getInstance();
    // retrieve current user's pending notifications ids, if any.
    $notifications_ids = $pdm->get('notifications', []);
    
    if(count($notifications_ids)) {
        $om = &ObjectManager::getInstance();        
        $notifications = $om->read('resiway\UserNotification', $notifications_ids, ['id', 'created', 'title', 'content']);
        if($notifications > 0) {
            $result = array_values($notifications);
        }        
        // reset pending notifications
        $pdm->set('notifications', []);
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

