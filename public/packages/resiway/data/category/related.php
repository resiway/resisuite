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
    'description'	=>	"Provide all existing categories",
    'params' 		=>	array(
                        'category_id'	=> array(
                                            'description'   => 'Identifier of the category we want to retrieve related catgeories',
                                            'type'          => 'integer',
                                            'required'      => true
                                            ),
                        'limit'		=> array(
                                            'description'   => 'The maximum number of results.',
                                            'type'          => 'integer',
                                            'min'           => 5,
                                            'max'           => 15,
                                            'default'       => 10
                                            ),                                            
                        'channel'	    => array(
                                            'description'   => 'Channel for which categories are requested (default, help, meta, ...)',
                                            'type'          => 'integer',
                                            'default'       => 1
                                            )
                        )
	)
);

list($result, $error_message_ids) = [true, []];

list($object_class, $object_id) = ['resiway\Category', $params['category_id']];


try {
    $om = &ObjectManager::getInstance();
    
    $res = $om->read($object_class, $object_id, ['path', 'parent_path']);    
    if($res < 0 || !isset($res[$object_id])) throw new Exception("object_unknown", QN_ERROR_INVALID_PARAM);       
    
    $domain = QNLib::domain_condition_add([], ['channel_id','=', $params['channel']]);
    $domain = QNLib::domain_condition_add($domain, ['count_questions','>', 0]);    
    
    if(strlen($res[$object_id]['parent_path']) > 0) {
        $domain = QNLib::domain_condition_add($domain, ['path', 'like', $res[$object_id]['parent_path'].'/%']);
    }
    else {
        $domain = QNLib::domain_condition_add($domain, ['path', 'like', $res[$object_id]['path'].'/%']);        
    }
    
    $categories_ids = $om->search('resiway\Category', $domain, 'count_questions', 'desc', 0, $params['limit']);
    
    $categories_ids = array_diff($categories_ids, [$object_id]);
    
    if(!empty($categories_ids)) {    
        // retrieve categories
        $res = $om->read('resiway\Category', $categories_ids, ['id', 'title', 'count_questions']);
        if($res < 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);
        $result = array_values($res);
    }
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