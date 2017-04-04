<?php
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;


// force silent mode (debug output would corrupt json data)
set_silent(true);


// announce script and fetch parameters values
$params = QNLib::announce(	
	array(	
    'description'	=>	"Returns actions matching given criteria.",
    'params' 		=>	array(
                        'domain'		=> array(
                                            'description' => 'The domain holds the criteria that results have to match (serie of conjunctions)',
                                            'type' => 'array',
                                            'default' => []
                                            ),
                        'page'		    => array(
                                            'description' => 'The page we\'re interested in (page length is set with \'rp\' parameter).',
                                            'type' => 'int',
                                            'default' => 1
                                            ),
                        'rp'		    => array(
                                            'description' => 'Number of rows we want to have into the list.',
                                            'type' => 'int',
                                            'default' => 10
                                            ),
                        'sortname'		=> array(
                                            'description' => 'Column to use for sorting results.',
                                            'type' => 'string',
                                            'default' => 'id'
                                            ),
                        'sortorder'		=> array(
                                            'description' => 'The direction  (i.e. \'asc\' or \'desc\').',
                                            'type' => 'string',
                                            'default' => 'asc'
                                            ),
                        'records'		=> array(
                                            'description' => 'Number of records in the list (if already known)',
                                            'type' => 'string',
                                            'default' => null
                                            ),
                        'mode'		    => array(
                                            'description' => 'Allows to limit result to deleted objects (when value is \'recycle\')',
                                            'type' => 'string',
                                            'default' => null
                                            ),
                        'lang'			=> array(
                                            'description '=> 'Specific language for multilang field.',
                                            'type' => 'string',
                                            'default' => DEFAULT_LANG
                                            )                                            
                        )
	)
);


list($object_class, $start, $domain) = ['resiway\Action', ($params['page']-1) * $params['rp'], $params['domain']];

list($result, $error_message_ids) = [ResiAPI::userId(), []];

try {
    $om = &ObjectManager::getInstance();

    if($start < 0) $start = 0;
    
    
    // 3) search and browse
    if(empty($params['records'])) {
        // We search all possible results. It might take some time (the bigger the tables, the longer it takes to process them)
        // but it is the only way to determine the number of results,
        // so we do it only when the number of results is unknown.
        $ids = $om->search($object_class, $domain, $params['sortname'], $params['sortorder'], 0, '', $params['lang']);
        if($ids < 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);
        $records_count = count($ids);
        $ids = array_slice($ids, $start , $params['rp'], true);
    }
    else {
        // This is a faster way to do the search but it requires the number of total results.
        $ids = $om->search($object_class, $domain, $params['sortname'], $params['sortorder'], $start, $params['rp'], $params['lang']);
        $records_count = $params['records'];
    }    
    $res = $om->read($object_class, $ids, $params['fields'], $params['lang']);
    if($res < 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);

    $result = [];
    $result['list'] = [];
    foreach($res as $action_id => $action_data) {
        $action = $action_data['name'];
        $result['list'][$action] = [];
        $result['list'][$action]['reputation'] = $action_data['required_reputation'];
    }

    $result['page'] = $params['page'];
    $result['total'] = ceil($records_count/$params['rp']);
    $result['records'] = $records_count;
}
catch(Exception $e) {
    $result = $e->getCode();
    $error_message_ids = array($e->getMessage());
}

// send json result
header('Content-type: application/json; charset=UTF-8');
echo json_encode(array(
            'result' => $result, 
            'error_message_ids' => $error_message_ids
            ), JSON_PRETTY_PRINT);