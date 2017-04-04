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
    'description'	=>	"Reset user password to a new value.",
    'params' 		=>	array(
                        'password'	=>  array(
                                        'description'   => 'new password.',
                                        'type'          => 'string', 
                                        'required'      => true
                                        ),
                        'confirm'	=>  array(
                                        'description'   => 'confirmation (has to be the same as password).',
                                        'type'          => 'string', 
                                        'required'      => true
                                        )
                        )
	)
);


list($result, $error_message_ids) = [true, []];
list($action_name, $object_class, $object_id) = [ 
    'resiway_user_passwordreset',
    'resiway\User',
    ResiAPI::userId()
];


try {
    $result = ResiAPI::performAction(
        $action_name,                                             // $action_name
        $object_class,                                            // $object_class
        $object_id,                                               // $object_id
        [                                                         // $object_fields  
        'verified', 'password'
        ],                                                       
        false,                                                    // $toggle
        function ($om, $user_id, $object_class, $object_id)       // $do
        use ($params) {
            // update password
            $res = $om->write($object_class, $object_id, [ 'password' => $params['confirm'] ]);
            return $res;
        },
        null,                                                      // $undo
        [                                                          // $limitations
            // user has to be verified in order to reset its password
            // (otherwise there might be a risk of breaking account validation process)            
            function ($om, $user_id, $action_id, $object_class, $object_id) {
                $res = $om->read($object_class, $object_id, ['verified']);
                if(!$res[$object_id]['verified']) {
                    throw new Exception("user_not_verified", QN_ERROR_NOT_ALLOWED);
                }        
            },
            // password and confirmation have to match
            function ($om, $user_id, $action_id, $object_class, $object_id) 
            use ($params) {                
                if($params['password'] != $params['confirm']) {
                    throw new Exception("confirm_no_match", QN_ERROR_INVALID_PARAM);                    
                }                
            },             
            // new password has to be in a valid format
            function ($om, $user_id, $action_id, $object_class, $object_id) 
            use ($params) {
                $userClass = &$om->getStatic('resiway\User');
                $constraints = $userClass::getConstraints();
                if(!$constraints['password']['function']($params['confirm'])) {
                    throw new Exception("user_invalid_password", QN_ERROR_INVALID_PARAM);                    
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
                    'result'            => $result, 
                    'error_message_ids' => $error_message_ids
                 ], JSON_PRETTY_PRINT);
