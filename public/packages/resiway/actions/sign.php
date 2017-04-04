<?php
use easyobject\orm\ObjectManager as ObjectManager;
use easyobject\orm\PersistentDataManager as PersistentDataManager;


list($result, $error_message_ids) = [true, []];

set_silent(true);

try {
    $om = &ObjectManager::getInstance();        
    $pdm = &PersistentDataManager::getInstance();
    
    // set identity as one of the random test-user
    $res = $om->search('resiway\User', [['login', 'like', 'resiway_u%']], 'reputation', 'asc', rand(0, 5), 1);
    $user_id = $res[0];
    $pdm->set('user_id', $user_id);
    $result = $user_id;
}
catch(Exception $e) {
    $result = $e->getCode();
    $error_message_ids = array($e->getMessage());
}

// send json result
header('Content-type: application/json; charset=UTF-8');
echo json_encode([
    'result' => $result, 
    'error_message_ids' => $error_message_ids
    ], 
    JSON_PRETTY_PRINT);