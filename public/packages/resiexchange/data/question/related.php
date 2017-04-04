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
                        'question_id'	=> array(
                                            'description'   => 'Identifier of the question we want to retrieve related questions',
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
                                            'description'   => 'Channel for which questions are requested (default, help, meta, ...)',
                                            'type'          => 'integer',
                                            'default'       => 1
                                            )
                        )
	)
);

list($result, $error_message_ids) = [true, []];

list($object_class, $object_id) = ['resiexchange\Question', $params['question_id']];


try {
    $om = &ObjectManager::getInstance();
    
    $res = $om->read($object_class, $object_id, ['categories_ids', 'categories_ids.path']);    
    if($res < 0 || !isset($res[$object_id])) throw new Exception("object_unknown", QN_ERROR_INVALID_PARAM);       
    
    $domain = QNLib::domain_condition_add([], ['channel_id','=', $params['channel']]);

    $categories_ids = $res[$object_id]['categories_ids'];
    $extra_categories_ids = [];
    
    if(count($categories_ids) < 4) {
        $subdomain = [];
        foreach($res[$object_id]['categories_ids.path'] as $category_path) {
            $subdomain = QNLib::domain_clause_add($subdomain, [['path', 'like', $category_path.'%']]);
        }
        $extra_categories_ids = $om->search('resiway\Category', $subdomain, 'count_questions', 'desc', 0, 5);
    }
    
    $domain = QNLib::domain_condition_add($domain, ['categories_ids', 'contains', array_merge($categories_ids, $extra_categories_ids)]);    
   
    $questions_ids = $om->search($object_class, $domain, 'score', 'desc', 0, $params['limit']);
    
    $questions_ids = array_diff($questions_ids, [$object_id]);
    
    if(!empty($questions_ids)) {    
        // retrieve categories
        $res = $om->read($object_class, $questions_ids, ['id', 'title', 'score', 'title_url']);
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