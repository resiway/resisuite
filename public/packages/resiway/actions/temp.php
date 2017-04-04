<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;
use html\HtmlToText as HtmlToText;
use qinoa\text\TextTransformer as TextTransformer;



// force silent mode (debug output would corrupt json data)
set_silent(false);

// index a question


/*
* Generate a 64-bits integer hash from given string
* returned value is intended to be stored in a BIGINT (8 bytes/20 digits) DBMS column
*/
function hashText($value) {
    return gmp_strval(gmp_init(substr(md5($value), 0, 16), 16), 10);
}



function indexQuestion($id) {
    
    $getKeywords = function ($string) {
        $string = HtmlToText::convert($string, false);
        $string = TextTransformer::normalize($string);
        $parts = explode(' ', $string);
        $result = [];
        foreach($parts as $part) {
            if(strlen($part) >= 3) $result[] = $part;
        }
        return $result;
    };
    
    $om = &ObjectManager::getInstance();
    $res = $om->read('resiexchange\Question', $id, ['title', 'content', 'categories_ids.title']);
print_r($res);    
    $keywords = [];
    foreach($res as $oids => $odata) {
        foreach($odata as $name => $value) {
            if(!is_array($value)) $value = (array) $value;
            foreach($value as $key => $str) {
                $keywords = array_merge($keywords, $getKeywords($str));
            }
        }
    }
 
    $hash_list = [];
    // we have all words related to the question :
    $db = $om->getDBHandler();
    // make sure all words are in the index
    foreach($keywords as $keyword) {
        $hash = TextTransformer::hash($keyword);
        if(in_array($hash, $hash_list)) continue;
        $hash_list[] = $hash;
        // $db->addRecords('resiway_index', ['hash', 'value'], [[$hash, $keyword]]);
        $db->sendQuery( 
            "INSERT INTO `resiway_index` (`hash`, `value`) SELECT $hash, '$keyword' FROM DUAL
            WHERE NOT EXISTS(SELECT `hash`, `value` FROM `resiway_index` WHERE `hash` = $hash AND `value` = '$keyword');"
            );
    }
    
    // obtain related ids of index entries to add to question
	$res = $db->sendQuery("SELECT id FROM resiway_index WHERE hash in ('".implode("','", $hash_list)."');");
    $index_ids = [];
    while($row = $db->fetchArray($res)) {
        $index_ids[] = $row['id'];
    }

    // add them to the index for given question
    $index_values = array_map(function ($a) use($id) { return [$id, $a]; }, $index_ids);  
    $db->addRecords('resiway_rel_index_question', ['question_id', 'index_id'], $index_values);
}



function searchFromIndex($query) {
    $result = [];
    $query = TextTransformer::normalize($query);
    $keywords = explode(' ', $query);
    $hash_list = array_map(function($a) { return TextTransformer::hash($a); }, $keywords);
    // we have all words related to the question :
    $om = &ObjectManager::getInstance();    
    $db = $om->getDBHandler();    
    // obtain related ids of index entries to add to question (don't mind the collision / false-positive)
	$res = $db->sendQuery("SELECT id FROM resiway_index WHERE hash in ('".implode("','", $hash_list)."');");
    $index_ids = [];
    while($row = $db->fetchArray($res)) {
        $index_ids[] = $row['id'];
    }
    
    if(count($index_ids)) {
        $res = $db->sendQuery("SELECT DISTINCT(question_id) FROM resiway_rel_index_question WHERE index_id in ('".implode("','", $index_ids)."');");
        while($row = $db->fetchArray($res)) {
            $result[] = $row['question_id'];
        }
    }
    return $result;
}






echo '<!DOCTYPE html>
<html lang="fr" ng-app="resiexchange" id="top" ng-controller="rootController as rootCtrl">
    <head>
        <meta charset="utf-8">
    </head>
    <body>
';
echo '<pre>';


indexQuestion(1);
indexQuestion(3);
indexQuestion(4);
indexQuestion(6);
indexQuestion(20);


$questions_ids = searchFromIndex('habitat ipsum');
print_r($questions_ids);





