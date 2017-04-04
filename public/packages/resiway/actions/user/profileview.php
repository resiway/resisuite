<?php
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
use config\QNlib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;

require_once('../resi.api.php');

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce(	
	array(	
    'description'	=>	"Acknowledge user profile view.\nCounter is incremented only when registered users request a user profile.",
    'params' 		=>	array(
                        'id'	    =>  array(
                                        'description' => 'identifier of the user whom profile is being viewed.',
                                        'type' => 'string', 
                                        'required'=> true
                                        )
                        )
	)
);


list($result, $error_message_ids) = [true, []];

list($action_name, $object_class, $object_id) = [ 
    'resiway_user_profileview',
    'resiway\User',
    $params['id']
];

try {   
    $result = ResiAPI::performAction(
        $action_name,                                             // $action_name
        $object_class,                                            // $object_class
        $object_id,                                               // $object_id
        [                                                         // $object_fields  
        'count_views'
        ],                                                       
        false,                                                    // $toggle
        function ($om, $user_id, $object_class, $object_id)       // $do
        use ($params) {
            // read count_views
            $res = $om->read($object_class, $object_id, ['count_views']);
            // update count_view
            $om->write($object_class, $object_id, [ 'count_views' => $res[$object_id]['count_views'] + 1 ]);            
            // use previous count_view as returned value
            return $res[$object_id]['count_views'];
        },
        null,                                                      // $undo
        [                                                          // $limitations
            // user has no impact on its own profile
            function ($om, $user_id, $action_id, $object_class, $object_id) {
                if($user_id == $object_id) {
                    throw new Exception("user_is_owner", QN_ERROR_NOT_ALLOWED);
                }        
            }        
        ]
    );    

}
catch(Exception $e) {
    $error_message_ids = array($e->getMessage());
    $result = $e->getCode();
}

// send json result
header('Content-type: application/json; charset=UTF-8');
echo json_encode([
        'result'             => $result, 
        'error_message_ids'  => $error_message_ids
     ], JSON_PRETTY_PRINT);