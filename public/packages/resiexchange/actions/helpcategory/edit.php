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
    'description'	=>	"Edit a help category or submit a new one",
    'params' 		=>	[
        'category_id'	=> array(
                            'description'   => 'Identifier of the category being edited (a null identifier means creation of a new topic).',
                            'type'          => 'integer', 
                            'required'      => true
                            ),    
        'title'	        => array(
                            'description'   => 'Title of the submitted category.',
                            'type'          => 'string', 
                            'required'      => true
                            ),
        'description'	=> array(
                            'description'   => 'Content of the submitted category.',
                            'type'          => 'string', 
                            'required'      => true
                            )
    ]
]);


list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class, $object_id) = [ 
    'resiexchange_helpcategory_edit',
    'resiexchange\HelpCategory',
    $params['category_id']
];


// handle new category submission 
// which has a distinct reputation requirement
if($object_id == 0) $action_name = 'resiexchange_helpcategory_post';


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
                // create a new category + write given value
                $object_id = $om->create($object_class, [ 
                                'creator'           => $user_id,     
                                'title'             => $params['title'],
                                'description'       => $params['description']
                              ]);

                if($object_id <= 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);
            }
            else {
                $om->write($object_class, $object_id, [
                                'modifier'          => $user_id, 
                                'title'             => $params['title'],
                                'description'       => $params['description']
                           ]);
            }
            
            // read created category as returned value
            $res = $om->read($object_class, $object_id, ['id', 'creator', 'created', 'title', 'title_url', 'description']);
            if($res < 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);
          
            return $res[$object_id];
        },
        null,                                                      // $undo
        [                                                          // $limitations
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