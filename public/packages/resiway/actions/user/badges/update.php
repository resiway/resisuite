<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use easyobject\orm\PersistentDataManager as PersistentDataManager;
use easyobject\orm\ObjectManager as ObjectManager;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce([
    'description'	=>	"Check for badge update based on current user's pending actions",
    'params' 		=>	[
    ]
]);


list($result, $error_message_ids) = [true, []];

/*
* This process is separated from the performAction method,
* so it can be invoked asynchronously, avoiding unnecessary delay
* for js-client while performing actions
*/
try {

    $pdm = &PersistentDataManager::getInstance();
    // retrieve current user's pending actions     
    $actions_ids = $pdm->get('actions', []);
    // fetch logs
    $om = &ObjectManager::getInstance();
    $res = $om->read('resiway\ActionLog', $actions_ids, ['action_id', 'object_class', 'object_id']);    
    // update badges
    if($res > 0 && count($actions_ids)) {
        foreach($res as $actionLog_id => $actionLog) {
            ResiAPI::updateBadges(
                $actionLog['action_id'],
                $actionLog['object_class'],
                $actionLog['object_id']
            );            
        }
    }
    // reset pending actions list
    $pdm->set('actions', []);    
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