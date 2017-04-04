<?php
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNlib as QNLib;


// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce(	
	array(	
    'description'	=>	"Returns current user identifier."
	)
);

list($result, $error_message_ids) = [ResiAPI::userId(), []];

// send json result
header('Content-type: application/json; charset=UTF-8');
echo json_encode(array('result' => $result, 'error_message_ids' => $error_message_ids), JSON_PRETTY_PRINT);