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
    'description'	=>	"Edit an answer",
    'params' 		=>	[
        'answer_id'	=> array(
                            'description'   => 'Identifier of the answer being edited.',
                            'type'          => 'integer', 
                            'default'       => 0
                            ),    
        'content'	    => array(
                            'description'   => 'New content of the edited answer.',
                            'type'          => 'string', 
                            'required'      => true
                            ),
    ]
]);


list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class, $object_id) = [ 
    'resiexchange_answer_edit',
    'resiexchange\Answer',
    $params['answer_id']
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
        [],                                                       // $object_fields
        false,                                                    // $toggle
        function ($om, $user_id, $object_class, $object_id)       // $do
        use ($params) {    
        
            $om->write($object_class, $object_id, [
                            'editor'            => $user_id, 
                            'edited'            => date("Y-m-d H:i:s"),
                            'content'           => $params['content']
                       ]);
            
            // read updated answer as returned value       
            $res = $om->read($object_class, $object_id, ['creator', 'created', 'question_id', 'content', 'content_excerpt', 'score']);
            if($res > 0) {
                $result = array(
                                'id'                => $object_id,
                                'creator'           => ResiAPI::loadUserPublic($user_id), 
                                'created'           => $res[$object_id]['created'],
                                'question_id'       => $res[$object_id]['question_id'],                                
                                'content'           => $res[$object_id]['content'],
                                'content_excerpt'   => $res[$object_id]['content_excerpt'],                                 
                                'score'             => $res[$object_id]['score'],
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
                    throw new Exception("answer_content_length_invalid", QN_ERROR_INVALID_PARAM); 
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