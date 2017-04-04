<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;


// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce([
    'description'	=>	"Adds a new comment to an answer",
    'params' 		=>	[
        'answer_id'	    => array(
                            'description'   => 'Identifier of the answer the user is commenting.',
                            'type'          => 'integer', 
                            'required'      => true
                            ),
        'content'	    => array(
                            'description'   => 'Short text the user is submitting as comment.',
                            'type'          => 'string', 
                            'required'      => true
                            )
    ]
]);

list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class, $object_id) = [ 
    'resiexchange_answer_comment',                         
    'resiexchange\Answer',                                   
    $params['answer_id']
];

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
            // create a new comment + write given value
            $comment_id = $om->create('resiexchange\AnswerComment', [ 
                            'creator'       => $user_id,     
                            'answer_id'     => $object_id,
                            'content'       => $params['content']
                          ]);

            if($comment_id <= 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);

            // update user count_comments
            $res = $om->read('resiway\User', $user_id, ['count_comments']);
            if($res > 0 && isset($res[$user_id])) {
                $om->write('resiway\User', $user_id, [ 'count_comments'=> $res[$user_id]['count_comments']+1 ]);
            }

            // update global counter
            ResiAPI::repositoryInc('resiexchange.count_comments');
            
            // read created comment as returned value
            $res = $om->read('resiexchange\AnswerComment', $comment_id, ['creator', 'created', 'content', 'score']);
            if($res > 0) {
                $result = array(
                            'id'        => $comment_id,
                            'creator'   => ResiAPI::loadUserPublic($user_id), 
                            'created'   => $res[$comment_id]['created'], 
                            'content'   => $res[$comment_id]['content'], 
                            'score'     => $res[$comment_id]['score'],
                            'history'   => []
                          );
            }
            else $result = $res;
            return $result;
        },
        null,                                                      // $undo
        [                                                          // $limitations
            // user cannot perform given action more than daily maximum
            function ($om, $user_id, $action_id, $object_class, $object_id) {
                $res = $om->search('resiway\ActionLog', [
                            ['user_id',     '=',  $user_id], 
                            ['action_id',   '=',  $action_id], 
                            ['object_class','=',  $object_class], 
                            ['created',     '>=', date("Y-m-d")]
                       ]);
                if($res > 0 && count($res) > RESIEXCHANGE_COMMENTS_DAILY_MAX) {
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