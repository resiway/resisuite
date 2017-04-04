<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce(	
	array(	
    'description'	=>	"Returns a category object",
    'params' 		=>	array(                                         
                        'id'	        => array(
                                            'description' => 'Identifier of the category to retrieve.',
                                            'type' => 'integer', 
                                            'required'=> true
                                            ),                                            
                        )
	)
);

list($object_class, $object_id) = ['resiway\Category', $params['id']];

list($result, $error_message_ids) = [true, []];



try {
    
    $om = &ObjectManager::getInstance();
  
    // retrieve given user
    $res = $om->read($object_class, $object_id, ['id', 'title', 'description', 'path', 'parent_id', 'parent_id.title', 'parent_id.path']);
    if($res < 0 || !isset($res[$object_id])) throw new Exception("category_unknown", QN_ERROR_INVALID_PARAM);
    
    $result = $res[$object_id];
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