<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;
use qinoa\text\TextTransformer as TextTransformer;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce(
	array(	
    'description'	=>	"Returns a list of indexed keywords matching given query",
    'params' 		=>	array(                                         
                        'q'		=> array(
                                            'description'   => 'query string',
                                            'type'          => 'string',
                                            'default'       => ''
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

try {
    if(strlen($params['q']) >= 3) {
        $om = &ObjectManager::getInstance();
        $db = $om->getDBHandler();
        
        $query = TextTransformer::normalize($params['q']);        
        $query = TextTransformer::normalize($query);
        $keywords = explode(' ', $query);
        
        $sql_clause = [];
        foreach($keywords as $keyword) {
            if(strlen($keyword) >= 3) {
                $sql_clause[] = "`value` like '{$keyword}%'";
            }
        }
        
        // obtain related ids of index entries 
        $res = $db->sendQuery("SELECT DISTINCT(`value`) FROM `resiway_index` WHERE ".implode(' OR ', $sql_clause)." LIMIT 0,10;");
        while($row = $db->fetchArray($res)) {
            $result[] = $row['value'];
        }
        $total = count($result);
    }
    else $total = 0;
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