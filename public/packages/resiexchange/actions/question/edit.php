<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use html\HTMLPurifier as HTMLPurifier;
use easyobject\orm\DataAdapter as DataAdapter;
use easyobject\orm\PersistentDataManager as PersistentDataManager;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce([
    'description'	=>	"Edit a question or submit a new one",
    'params' 		=>	[
        'question_id'	=> array(
                            'description'   => 'Identifier of the question being edited (a null identifier means creation of a new question).',
                            'type'          => 'integer', 
                            'default'       => 0
                            ),    
        'title'	        => array(
                            'description'   => 'Title of the submitted question.',
                            'type'          => 'string', 
                            'required'      => true
                            ),
        'content'	    => array(
                            'description'   => 'Content of the submitted question.',
                            'type'          => 'string', 
                            'default'       => ''
                            ),
        'tags_ids'      => array(
                            'description'   => 'List of tags assigned to the question.',
                            'type'          => 'array',
                            'required'      => true
                            ),
        'channel_id'    => array(
                            'description'   => 'Identifier of the channel to which store the question.',
                            'type'          => 'integer',
                            'default'       => 1
                            ),
                            
    ]
]);


list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class, $object_id) = [ 
    'resiexchange_question_edit',
    'resiexchange\Question',
    $params['question_id']
];

// override ORM method for cleaning HTML (for field 'content')
DataAdapter::setMethod('ui', 'orm', 'html', function($value) {
    $purifier = new HTMLPurifier(ResiAPI::getHTMLPurifierConfig());    
    return $purifier->purify($value);
});


// handle case of new question submission (which has a distinct reputation requirement)
if($object_id == 0) $action_name = 'resiexchange_question_post';



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
            $pdm = &PersistentDataManager::getInstance();
            
            // check tags_ids consistency (we might have received a request for new categories)
            foreach($params['tags_ids'] as $key => $value) {
                if(intval($value) == 0 && strlen($value) > 0) {
                    // create a new category + write given value
                    $tag_id = $om->create('resiway\Category', [ 
                                    'creator'           => $user_id,     
                                    'title'             => $value,
                                    'description'       => '',
                                    'parent_id'         => 0,
                                    'channel_id'        => $pdm->get('channel', 1)
                                  ]);
                    // update entry
                    $params['tags_ids'][$key] = sprintf("+%d", $tag_id);
                }
            }        
            
            if($object_id == 0) {
            
                // create a new question + write given value
                $object_id = $om->create('resiexchange\Question', [ 
                                'creator'           => $user_id,     
                                'title'             => $params['title'],
                                'content'           => $params['content'],
                                'categories_ids'    => $params['tags_ids'],
                                'channel_id'        => $pdm->get('channel', 1)
                              ]);

                if($object_id <= 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);

                // update user count_questions
                $res = $om->read('resiway\User', $user_id, ['count_questions']);
                if($res > 0 && isset($res[$user_id])) {
                    $om->write('resiway\User', $user_id, [ 'count_questions'=> $res[$user_id]['count_questions']+1 ]);
                }

                // update categories count_questions
                $res = $om->read('resiway\Category', $params['tags_ids'], ['count_questions']);
                if($res > 0) {
                    foreach($res as $cat_id => $cat_data) {
                        $om->write('resiway\Category', $cat_id, [ 'count_questions'=> $cat_data['count_questions']+1 ]);
                    }                    
                }
                
                // update global counter
                ResiAPI::repositoryInc('resiexchange.count_questions');
            }
            else {
                /*
                 note : expected notation of tags_ids involve a sign 
                 '+': relation to be added
                 '-': relation to be removed
                */
                $om->write($object_class, $object_id, [
                                'editor'            => $user_id, 
                                'edited'            => date("Y-m-d H:i:s"),
                                'title'             => $params['title'],
                                'content'           => $params['content'],
                                'categories_ids'    => $params['tags_ids']
                           ]);

                // update categories count_questions
                $categories_ids = array_map(function($i) { return abs(intval($i)); }, $params['tags_ids']);                
                $res = $om->read('resiway\Category', $categories_ids, ['count_questions']);
                if($res > 0) {
                    foreach($categories_ids as $index => $category_id) {
                        $sign = ($params['tags_ids'][$index] > 0) - ($params['tags_ids'][$index] < 0);
                        $om->write('resiway\Category', $category_id, [ 'count_questions'=> $res[$category_id]['count_questions']+$sign ]);
                    }                    
                }
            }
            
            // read created question as returned value
            $res = $om->read($object_class, $object_id, ['creator', 'created', 'title', 'content', 'content_excerpt', 'score', 'categories_ids']);
            if($res > 0) {
                $result = array(
                                'id'                => $object_id,
                                'creator'           => ResiAPI::loadUserPublic($user_id), 
                                'created'           => $res[$object_id]['created'], 
                                'title'             => $res[$object_id]['title'],                             
                                'content'           => $res[$object_id]['content'],
                                'content_excerpt'   => $res[$object_id]['content_excerpt'],                                 
                                'score'             => $res[$object_id]['score'],
                                'tags_ids'          => $res[$object_id]['categories_ids'],
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
                if(strlen($params['title']) < RESIEXCHANGE_QUESTION_TITLE_LENGTH_MIN
                || strlen($params['title']) > RESIEXCHANGE_QUESTION_TITLE_LENGTH_MAX) {
                    throw new Exception("question_title_length_invalid", QN_ERROR_INVALID_PARAM); 
                }
                if(strlen($params['content']) < RESIEXCHANGE_QUESTION_CONTENT_LENGTH_MIN
                || strlen($params['content']) > RESIEXCHANGE_QUESTION_CONTENT_LENGTH_MAX) {
                    throw new Exception("question_content_length_invalid", QN_ERROR_INVALID_PARAM); 
                }
                $count_tags = 0;
                foreach($params['tags_ids'] as $tag_id) {
                    if(intval($tag_id) > 0) ++$count_tags;
                    else if(intval($tag_id) == 0 && strlen($tag_id) > 0) ++$count_tags;
                }
                if($count_tags < RESIEXCHANGE_QUESTION_CATEGORIES_COUNT_MIN
                || $count_tags > RESIEXCHANGE_QUESTION_CATEGORIES_COUNT_MAX) {
                    throw new Exception("question_tags_count_invalid", QN_ERROR_INVALID_PARAM); 
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
                if($res > 0 && count($res) > RESIEXCHANGE_QUESTIONS_DAILY_MAX) {
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