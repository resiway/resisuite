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
    'description'	=>	"Returns a list of log objects matching the received criteria",
    'params' 		=>	array(                                         
                        'domain'	=> array(
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
                                            'default'       => 25                                            
                                            ),
                        'total'		=> array(
                                            'description'   => 'Total of record (if known).',
                                            'type'          => 'integer',
                                            'default'       => -1
                                            )                                              
                        )
	)
);

/*
 @actions   this is a data provider: no change is made to the stored data
 @rights    everyone has read access on these data
 @returns   list of logs matching given criteria
*/


list($result, $error_message_ids, $total) = [[], [], $params['total']];


try {
    
    $om = &ObjectManager::getInstance();

    // check user permissions (prevent accessing other user datalog)

    $user_id = ResiAPI::userId();
    if($user_id < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
    
    // retrieve given user data 
    $user = ResiAPI::loadUserPublic($user_id);

    $params['domain'] = QNLib::domain_normalize($params['domain']);
    foreach($params['domain'] as $clause) {
        foreach($clause as $condition) {
            if($condition[0] == 'user_id') {
                if($condition[2] == $user_id) break 2;
                if($user['role'] != 'a') throw new Exception("user_not_owner", QN_ERROR_NOT_ALLOWED);
            }
        }
    }
    
    // 0) retrieve matching logs identifiers    
    
    // total is not knwon yet
    if($params['total'] < 0) {        
        $ids = $om->search('resiway\ActionLog', $params['domain'], $params['order'], $params['sort']);
        if($ids < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
        $total = count($ids);
		$logs_ids = array_slice($ids, $params['start'], $params['limit']);
    }
    else {
        $logs_ids = $om->search('resiway\ActionLog', $params['domain'], $params['order'], $params['sort'], $params['start'], $params['limit']);
        if($logs_ids < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
    }
    
    if(!empty($logs_ids)) {
        // retrieve logs
        $res = $om->read('resiway\ActionLog', $logs_ids, ['id', 'created', 'user_id', 'author_id', 'object_name', 'action_id', 'action_id.name', 'action_id.description', 'user_increment', 'author_increment', 'object_class', 'object_id']);
        if($res < 0 || !count($res)) throw new Exception("request_failed", QN_ERROR_UNKNOWN);

        $logs = [];
        foreach($res as $log_id => $log_data) {                
            $logs[$log_id] = array(
                                        'id'                    => $log_id,
                                        'description'           => $log_data['action_id.description'],
                                        'user_id'               => $log_data['user_id'],  
                                        'author_id'             => $log_data['author_id'],                                          
                                        'created'               => $log_data['created'],
                                        'user_increment'        => $log_data['user_increment'],
                                        'author_increment'      => $log_data['author_increment'],                                        
                                        'object_name'           => $log_data['object_name'],                                        
                                        'object_class'          => $log_data['object_class'],
                                        'object_id'             => $log_data['object_id']
                                       );
        }        
            
        $result = array_values($logs);
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