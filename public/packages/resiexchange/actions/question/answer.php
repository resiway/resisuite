<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use html\HTMLPurifier as HTMLPurifier;
use easyobject\orm\DataAdapter as DataAdapter;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce([
    'description'	=>	"Registers a question as favorite for current user",
    'params' 		=>	[
        'question_id'	=> array(
                            'description'   => 'Identifier of the question the answer refers to.',
                            'type'          => 'integer', 
                            'required'      => true
                            ),
        'content'	    => array(
                            'description'   => 'Content of the submitted answer.',
                            'type'          => 'string', 
                            'required'      => true
                            )
    ]
]);

list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class, $object_id) = [ 
    'resiexchange_question_answer',                         
    'resiexchange\Question',                                   
    $params['question_id']
];

// override ORM method for cleaning HTML
DataAdapter::setMethod('ui', 'orm', 'html', function($value) {
    $purifier = new HTMLPurifier(ResiAPI::getHTMLPurifierConfig());    
    return $purifier->purify($value);
});


try {
    
    // try to perform action
    $result = ResiAPI::performAction(
        $action_name,                                             // $action_name
        $object_class,                                            // $object_class
        $object_id,                                               // $object_id
        ['count_answers'],                                        // $object_fields
        false,                                                    // $toggle
        function ($om, $user_id, $object_class, $object_id)       // $do
        use ($params) {    
            // create a new answer + write given value
            $answer_id = $om->create('resiexchange\Answer', [ 
                            'creator'           => $user_id,     
                            'question_id'       => $object_id,
                            'content'           => $params['content']
                          ]);

            if($answer_id <= 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);

            // update user count_answers
            $res = $om->read('resiway\User', $user_id, ['count_answers']);
            if($res > 0 && isset($res[$user_id])) {
                $om->write('resiway\User', $user_id, [ 'count_answers'=> $res[$user_id]['count_answers']+1 ]);
            }

            // update question count_answers
            $object = $om->read($object_class, $object_id, ['count_answers'])[$object_id];       
            $om->write($object_class, $object_id, [
                'count_answers' => $object['count_answers']+1
            ]);
            
            // update global counter
            ResiAPI::repositoryInc('resiexchange.count_answers');
            
            // read created answer as returned value
            $res = $om->read('resiexchange\Answer', $answer_id, ['creator', 'created', 'content', 'content_excerpt', 'score']);
            if($res > 0) {
                $result = array(
                            'id'                => $answer_id,
                            'creator'           => ResiAPI::loadUserPublic($user_id), 
                            'created'           => $res[$answer_id]['created'], 
                            'content'           => $res[$answer_id]['content'], 
                            'content_excerpt'   => $res[$answer_id]['content_excerpt'],                             
                            'score'             => $res[$answer_id]['score'],
                            'comments'          => [],                            
                            'history'           => []
                          );
            }
            else $result = $res;
            return $result;
        },
        null,                                                      // $undo
        [                                                          // $limitations
            function ($om, $user_id, $action_id, $object_class, $object_id) 
            use ($params) {
                if(strlen($params['content']) < RESIEXCHANGE_ANSWER_CONTENT_LENGTH_MIN
                || strlen($params['content']) > RESIEXCHANGE_ANSWER_CONTENT_LENGTH_MAX) {
                    throw new Exception("content_length_invalid", QN_ERROR_INVALID_PARAM); 
                }
            },
            // user cannot perform action on an object more than once
            function ($om, $user_id, $action_id, $object_class, $object_id) {
                if(ResiAPI::isActionRegistered($user_id, $action_id, $object_class, $object_id)) {
                    throw new Exception("action_already_performed", QN_ERROR_NOT_ALLOWED);  
                }        
            },        
            // user cannot perform given action more than daily maximum
            function ($om, $user_id, $action_id, $object_class, $object_id) {
                $res = $om->search('resiway\ActionLog', [
                            ['user_id',     '=',  $user_id], 
                            ['action_id',   '=',  $action_id], 
                            ['object_class','=',  $object_class], 
                            ['created',     '>=', date("Y-m-d")]
                       ]);
                if($res > 0 && count($res) > RESIEXCHANGE_ANSWERS_DAILY_MAX) {
                    throw new Exception("action_max_reached", QN_ERROR_NOT_ALLOWED);
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