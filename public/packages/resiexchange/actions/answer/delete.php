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
    'description'	=>	"Delete an answer",
    'params' 		=>	[
        'answer_id'	=> [
            'description'   => 'Identifier of the answer to delete.',
            'type'          => 'integer', 
            'required'      => true
        ]
    ]
]);

list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class, $object_id) = [ 
    'resiexchange_answer_delete',                         
    'resiexchange\Answer',                                   
    $params['answer_id']
];

try {
    
    // try to perform action
    $result = ResiAPI::performAction(
        $action_name,                                               // $action_name
        $object_class,                                              // $object_class
        $object_id,                                                 // $object_id
        ['creator', 'deleted', 'question_id'],                      // $object_fields
        true,                                                       // $toggle
        function ($om, $user_id, $object_class, $object_id) {       // $do
            // retreive related question id
            $objects = $om->read($object_class, $object_id, ['question_id']);            
            // retrieve related action object                      
            $related_object_class = 'resiexchange\Question';
            $related_object_id = $objects[$object_id]['question_id'];
            // undo related action            
            ResiAPI::unregisterAction($user_id, 'resiexchange_question_answer', $related_object_class, $related_object_id);        
            // update deletion status
            $om->write($object_class, $object_id, [
                        'deleted' => 1
                      ]);
            // update related question count_answers
            $object = $om->read($related_object_class, $related_object_id, ['count_answers'])[$related_object_id];       
            $om->write($related_object_class, $related_object_id, [
                'count_answers' => $object['count_answers']-1
            ]);                      
            return true;
        },
        function ($om, $user_id, $object_class, $object_id) {       // $undo
            // retreive related question id
            $objects = $om->read($object_class, $object_id, ['question_id']);            
            // retrieve related action object                      
            $related_object_class = 'resiexchange\Question';
            $related_object_id = $objects[$object_id]['question_id'];
            // perform related action
            ResiAPI::registerAction($user_id, 'resiexchange_question_answer', $related_object_class, $related_object_id);
            // update deletion status
            $om->write($object_class, $object_id, [
                        'deleted' => 0
                      ]);    
            // update related question count_answers
            $object = $om->read($related_object_class, $related_object_id, ['count_answers'])[$related_object_id];       
            $om->write($related_object_class, $related_object_id, [
                'count_answers' => $object['count_answers']+1
            ]);                         
            return false;
        },
        [                                                           // $limitations
            // user has to be admin or owner
            function ($om, $user_id, $action_id, $object_class, $object_id) {
                $object = $om->read($object_class, $object_id, ['creator'])[$object_id];
                if($user_id != $object['creator']
                && $user_id != 1) {
                    throw new Exception("user_not_owner", QN_ERROR_NOT_ALLOWED);  
                }             
            }
     
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