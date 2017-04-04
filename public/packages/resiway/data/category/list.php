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
                        'domain'		=> array(
                                            'description'   => 'Criterias that results have to match (serie of conjunctions)',
                                            'type'          => 'array',
                                            'default'       => []
                                            ),
                        'order'	        => array(
                                            'description'   => 'Field on which sort the categories',
                                            'type'          => 'string',
                                            'default'       => 'count_questions'
                                            ),
                        'sort'		=> array(
                                            'description'   => 'The direction  (i.e. \'asc\' or \'desc\').',
                                            'type'          => 'string',
                                            'default'       => 'desc'
                                            ),
                        'start'		=> array(
                                            'description'   => 'The row from which results have to start.',
                                            'type'          => 'integer',
                                            'default'       => 0
                                            ),
                        'limit'		=> array(
                                            'description'   => 'The maximum number of results.',
                                            'type'          => 'integer',
                                            'min'           => 5,
                                            'max'           => 150,
                                            'default'       => 30
                                            ),                                            
                        'total'		=> array(
                                            'description'   => 'Total of record (if known).',
                                            'type'          => 'integer',
                                            'default'       => -1
                                            ),
                        'channel'	    => array(
                                            'description'   => 'Channel for which categories are requested (default, help, meta, ...)',
                                            'type'          => 'integer',
                                            'default'       => 1
                                            )
                        )
	)
);

list($result, $error_message_ids, $total) = [true, [], $params['total']];



try {

    $om = &ObjectManager::getInstance();
    
    $params['domain'] = QNLib::domain_normalize($params['domain']);
    if(!QNLib::domain_check($params['domain'])) $params['domain'] = [];
    
    $params['domain'] = QNLib::domain_condition_add($params['domain'], ['channel_id','=', $params['channel']]);

    // total is not knwon yet
    if($params['total'] < 0) {        
        $ids = $om->search('resiway\Category', $params['domain'], $params['order'], $params['sort']);
        if($ids < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
        $total = count($ids);
		$tags_ids = array_slice($ids, $params['start'], $params['limit']);
    }
    else {
        $tags_ids = $om->search('resiway\Category', $params['domain'], $params['order'], $params['sort'], $params['start'], $params['limit']);
        if($tags_ids < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
    }

    if(!empty($tags_ids)) {    
        // retrieve categories
        $res = $om->read('resiway\Category', $tags_ids, ['id', 'title', 'description', 'path', 'parent_id', 'parent_path', 'count_questions']);
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
                    'total'             => $total,
                    'error_message_ids' => $error_message_ids
                 ],
                 JSON_PRETTY_PRINT);