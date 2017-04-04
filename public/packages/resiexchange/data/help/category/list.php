<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;
use easyobject\orm\PersistentDataManager as PersistentDataManager;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce(	
	array(	
    'description'	=>	"Returns all help category objects with their topics lists",
    'params' 		=>	[
    ]
	)
);

// todo : adapt this to allow searches (sse question_list)

list($object_class) = ['resiexchange\HelpCategory'];

list($result, $error_message_ids) = [true, []];

try {
    
    $om = &ObjectManager::getInstance();
    $pdm = &PersistentDataManager::getInstance();
    
    // retrieve session lang
    $lang = $pdm->get('lang', DEFAULT_LANG);
    
    // retrieve categories
    $result = [];
    $categories_ids = $om->search($object_class, [], 'order');
    $res = $om->read($object_class, $categories_ids, ['id', 'title', 'title_url', 'description', 'topics_ids'], $lang);
    
    if($res < 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);
    
    $categories = [];
    
    // retrieve topics
    $topics_ids = [];
    foreach($res as $category_id => $category_data) {
        $topics_ids = array_merge($topics_ids, $category_data['topics_ids']);
        $categories[$category_id] = array(
                                        'id'                => $category_id,
                                        'title'             => $category_data['title'], 
                                        'title_url'         => $category_data['title_url'],                                         
                                        'description'       => $category_data['description']
                                    );
    }
    
    $topics_data = $om->read('resiexchange\HelpTopic', $topics_ids, ['title', 'title_url', 'content'], $lang);        
    if($topics_data > 0) {
        foreach($res as $category_id => $category_data) {
            $topics = [];            
            foreach($category_data['topics_ids'] as $topic_id) {
                $topics[$topic_id] = array(
                                            'id'            => $topic_id,
                                            'title'         => $topics_data[$topic_id]['title'], 
                                            'title_url'     => $topics_data[$topic_id]['title_url'],                                         
                                            'content'       => $topics_data[$topic_id]['content']
                                        );
            }
            $categories[$category_id]['topics'] = array_values($topics);            
        }      
    }
    
    $result = array_values($categories);
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