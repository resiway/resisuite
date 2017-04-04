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
    'description'	=>	"Registers a question as favorite for current user",
    'params' 		=>	[
        'question_id'	=> [
            'description'   => 'Identifier of the question to star.',
            'type'          => 'integer', 
            'required'      => true
        ]
    ]
]);

list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class, $object_id) = [ 
    'resiexchange_question_star',                         
    'resiexchange\Question',                                   
    $params['question_id']
];

try {
    
    // try to perform action
    $result = ResiAPI::performAction(
        $action_name,                                               // $action_name
        $object_class,                                              // $object_class
        $object_id,                                                 // $object_id
        ['count_stars'],                                            // $object_fields
        true,                                                       // $toggle
        function ($om, $user_id, $object_class, $object_id) {       // $do
            $objects = $om->read($object_class, $object_id, ['count_stars']);  
            // update question star count
            $om->write($object_class, $object_id, [
                        'count_stars' => $objects[$object_id]['count_stars']+1
                      ]);
            return true;
        },
        function ($om, $user_id, $object_class, $object_id) {       // $undo
            $objects = $om->read($object_class, $object_id, ['count_stars']);  
            // update question star count
            $om->write($object_class, $object_id, [
                        'count_stars' => $objects[$object_id]['count_stars']-1
                      ]);
            return false;
        }
    );

}
catch(Exception $e) {
    $result = $e->getCode();
    $error_message_ids = array($e->getMessage());
}

// send json result
header('Content-type: application/json; charset=UTF-8');
echo json_encode([
        'result'            => $result, 
        'error_message_ids' => $error_message_ids,
        'notifications'     => $notifications
    ], 
    JSON_PRETTY_PRINT);