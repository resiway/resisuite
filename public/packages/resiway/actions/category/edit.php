<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use easyobject\orm\PersistentDataManager as PersistentDataManager;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce([
    'description'	=>	"Edit a category or submit a new one",
    'params' 		=>	[
        'category_id'	=> array(
                            'description'   => 'Identifier of the category being edited (a null identifier means creation of a new category).',
                            'type'          => 'integer', 
                            'default'       => 0
                            ),    
        'title'	        => array(
                            'description'   => 'Title of the submitted category.',
                            'type'          => 'string', 
                            'required'      => true
                            ),
        'description'	=> array(
                            'description'   => 'Description of the submitted category.',
                            'type'          => 'string', 
                            'required'      => true
                            ),
        'parent_id'     => array(
                            'description'   => 'Identifier of the parent category.',
                            'type'          => 'integer'
                            ),                            
    ]
]);


list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class, $object_id) = [ 
    'resiway_category_edit',
    'resiway\Category',
    $params['category_id']
];



// handle new category submission 
// which has a distinct reputation requirement
if($object_id == 0) $action_name = 'resiway_category_post';


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
        
            if($object_id == 0) {
                $pdm = &PersistentDataManager::getInstance();      
                
                // create a new category + write given value
                $object_id = $om->create('resiway\Category', [ 
                                'creator'           => $user_id,     
                                'title'             => $params['title'],
                                'description'       => $params['description'],
                                'parent_id'         => $params['parent_id'],
                                'channel_id'        => $pdm->get('channel', 1)                                
                              ]);

                if($object_id <= 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);
            }
            else {
                $om->write($object_class, $object_id, [
                                'modifier'          => $user_id, 
                                'title'             => $params['title'],
                                'description'       => $params['description'],
                                'parent_id'         => $params['parent_id']
                           ]);
            }
            
            // read created category as returned value
            $res = $om->read($object_class, $object_id, ['creator', 'created', 'channel_id', 'title', 'description', 'parent_id', 'path', 'parent_path']);
            if($res > 0) {
                $result = array(
                                'id'                => $object_id,
                                'creator'           => ResiAPI::loadUserPublic($user_id), 
                                'created'           => $res[$object_id]['created'], 
                                'channel_id'        => $res[$object_id]['channel_id'],                                 
                                'title'             => $res[$object_id]['title'],                             
                                'description'       => $res[$object_id]['description'],
                                'path'              => $res[$object_id]['path'],                                 
                                'parent_path'       => $res[$object_id]['parent_path'],
                                'parent_id'         => $res[$object_id]['parent_id']
                          );
            }
            else $result = $res;            
            return $result;
        },
        null,                                                      // $undo
        [                                                          // $limitations
            function ($om, $user_id, $action_id, $object_class, $object_id) 
            use ($params) {
                if(strlen($params['title']) < RESIWAY_CATEGORY_TITLE_LENGTH_MIN
                || strlen($params['title']) > RESIWAY_CATEGORY_TITLE_LENGTH_MAX) {
                    throw new Exception("category_title_length_invalid", QN_ERROR_INVALID_PARAM); 
                }
                if(strlen($params['description']) < RESIWAY_CATEGORY_DESCRIPTION_LENGTH_MIN
                || strlen($params['description']) > RESIWAY_CATEGORY_DESCRIPTION_LENGTH_MAX) {
                    throw new Exception("category_description_length_invalid", QN_ERROR_INVALID_PARAM); 
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
                if($res > 0 && count($res) > RESIWAY_CATEGORIES_DAILY_MAX) {
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