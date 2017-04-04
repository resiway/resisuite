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
/*
 @actions   this is a data provider: no change is made to the stored data
 @rights    everyone has read access on these data
 @returns   list of badges matching given criteria
*/
	array(	
    'description'	=>	"Returns a list of badges objects matching the received criteria",
    'params' 		=>	array(                                         
                        'domain'		=> array(
                                            'description'   => 'Criterias that results have to match (serie of conjunctions)',
                                            'type'          => 'array',
                                            'default'       => []
                                            ),
                        'order'		=> array(
                                            'description'   => 'Column to use for sorting results.',
                                            'type'          => 'string',
                                            'default'       => 'id'
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
                                            'max'           => 100,
                                            'default'       => 30
                                            ),
                        'total'		=> array(
                                            'description'   => 'Total of record (if known).',
                                            'type'          => 'integer',
                                            'default'       => -1
                                            )                                              
                        )
	)
);




list($result, $error_message_ids, $total) = [[], [], $params['total']];
list($object_class) = ['resiway\UserBadge'];

try {    
    $om = &ObjectManager::getInstance();
    $pdm = &PersistentDataManager::getInstance();

    if(!is_array($params['domain'][0])) {
        $params['domain'] = [$params['domain']];
    }

    $params['domain'][] = ['awarded', '=', '1'];

    // retrieve session lang
    $lang = $pdm->get('lang', DEFAULT_LANG);
    
    // total is not knwon yet
    if($params['total'] < 0) {        
        $ids = $om->search($object_class, $params['domain'], $params['order'], $params['sort'], 0, 0, $lang);
        if($ids < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
        $total = count($ids);
		$badges_ids = array_slice($ids, $params['start'], $params['limit']);
    }
    else {
        $badges_ids = $om->search($object_class, $params['domain'], $params['order'], $params['sort'], $params['start'], $params['limit'], $lang);
        if($badges_ids < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
    }
    
    if(!empty($badges_ids)) {
        // retrieve objects
        $res = $om->read($object_class, $badges_ids, ['id', 'modified', 'badge_id', 'badge_id.name', 'badge_id.description', 'badge_id.type'], $lang);
        if($res < 0 || !count($res)) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
            
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