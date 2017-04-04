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
    'description'	=>	"Delete a question",
    'params' 		=>	[
        'question_id'	=> [
            'description'   => 'Identifier of the question to delete.',
            'type'          => 'integer', 
            'required'      => true
        ]
    ]
]);

list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class, $object_id) = [ 
    'resiexchange_question_delete',                         
    'resiexchange\Question',                                   
    $params['question_id']
];

try {
    
    // try to perform action
    $result = ResiAPI::performAction(
        $action_name,                                               // $action_name
        $object_class,                                              // $object_class
        $object_id,                                                 // $object_id
        ['creator', 'deleted'],                                     // $object_fields
        true,                                                       // $toggle
        function ($om, $user_id, $object_class, $object_id) {       // $do
            // update deletion status
            $om->write($object_class, $object_id, [
                        'deleted' => 1
                      ]);
// todo: update related categories questions_count
            ResiAPI::repositoryDec('resiexchange.count_questions');
            return true;
        },
        function ($om, $user_id, $object_class, $object_id) {       // $undo
            // update deletion status
            $om->write($object_class, $object_id, [
                        'deleted' => 0
                      ]);
// todo: update related categories questions_count                      
            ResiAPI::repositoryInc('resiexchange.count_questions');                      
            return false;
        },
        [                                                           // $limitations     
        ]
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
        'error_message_ids' => $error_message_ids
    ], 
    JSON_PRETTY_PRINT);