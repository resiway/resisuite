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
    'description'	=>	"Returns a list of answers objects matching the received criteria",
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
 @returns   list of answers matching given criteria
*/


list($result, $error_message_ids, $total) = [[], [], $params['total']];

try {
    
    $om = &ObjectManager::getInstance();

    // 0) retrieve matching answers identifiers
    
    // total is not knwon yet
    if($params['total'] < 0) {        
        $ids = $om->search('resiexchange\Answer', $params['domain'], $params['order'], $params['sort']);
        if($ids < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
        $total = count($ids);
		$answers_ids = array_slice($ids, $params['start'], $params['limit']);
    }
    else {
        $answers_ids = $om->search('resiexchange\Answer', $params['domain'], $params['order'], $params['sort'], $params['start'], $params['limit']);
        if($answers_ids < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
    }
    
    if(!empty($answers_ids)) {
        // retrieve answers
        $res = $om->read('resiexchange\Answer', $answers_ids, ['creator', 'created', 'question_id', 'title', 'content_excerpt', 'count_votes']);
        if($res < 0 || !count($res)) throw new Exception("request_failed", QN_ERROR_UNKNOWN);

        $authors_ids = [];

        $answers = [];
        foreach($res as $question_id => $answer_data) {    
            $answers[$question_id] = array(
                                        'id'            => $question_id,
                                        'creator'       => $answer_data['creator'],
                                        'created'       => $answer_data['created'],
                                        'question_id'   => $answer_data['question_id'],
                                        'title'         => $answer_data['title'],                                        
                                        'content'       => $answer_data['content_excerpt'],
                                        'count_votes'   => $answer_data['count_votes']
                                       );
        }    
       
        $result = array_values($answers);
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