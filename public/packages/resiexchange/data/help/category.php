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
    'description'	=>	"Returns a fully loaded help category object",
    'params' 		=>	array(                                         
                        'id'    => array(
                                    'description'   => 'Identifier of the category to retrieve.',
                                    'type'          => 'integer', 
                                    'required'      => true
                                    ),                                            
                        )
	)
);


list($object_class, $object_id) = ['resiexchange\HelpCategory', $params['id']];

list($result, $error_message_ids) = [true, []];

try {
    
    $om = &ObjectManager::getInstance();  
   
    // retrieve category
    $result = [];
    $res = $om->read($object_class, $object_id, ['id', 'title', 'title_url', 'description', 'topics_ids']);
    
    if($res < 0 || !isset($res[$object_id])) throw new Exception("helpCategory_unknown", QN_ERROR_INVALID_PARAM);
    
    $category_data = $res[$object_id];
    
    // retrieve topics
    $category_data['topics'] = [];
    $res = $om->read('resiexchange\HelpTopic', $category_data['topics_ids'], ['title', 'title_url', 'content']);
    if($res > 0) {
        $topics = [];
        foreach($res as $topic_id => $topic_data) {           
            $topics[$topic_id] = array(
                                        'id'            => $topic_id,
                                        'title'         => $topic_data['title'], 
                                        'title_url'     => $topic_data['title_url'],                                         
                                        'content'   => $topic_data['content'],                                         
                                    );
        }      
        
        // asign resulting array to returned value
        $category_data['topics'] = array_values($topics);
    }
    
    $result = $category_data;    
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