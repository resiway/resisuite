#!/usr/bin/env php
<?php
/**
 Tells indexer to update non-indexed questions
*/
use easyobject\orm\ObjectManager as ObjectManager;
use html\HtmlToText as HtmlToText;
use qinoa\text\TextTransformer as TextTransformer;

// run this script as if it were located in the public folder
chdir('../../public');
set_time_limit(0);

// this utility script uses qinoa library
// and requires file config/config.inc.php
require_once('../qn.lib.php');



list($result, $error_message_ids) = [true, []];

set_silent(false);


function extractKeywords($string) {
    $string = HtmlToText::convert($string, false);
    $string = TextTransformer::normalize($string);
    $parts = explode(' ', $string);
    $result = [];
    foreach($parts as $part) {
        if(strlen($part) >= 3) $result[] = TextTransformer::axiomize($part);
    }
    return $result;
}
    

try {
    $om = &ObjectManager::getInstance();
    // request a batch of 5 non-indexed questions
    $questions_ids = $om->search('resiexchange\Question', ['indexed', '=', 0], 'id', 'asc', 0, 5);
    if($questions_ids > 0 && count($questions_ids)) {
        foreach($questions_ids as $id) {
            // retrieve keywords from question
            $res = $om->read('resiexchange\Question', $id, ['title', 'content', 'categories_ids.title']);
            $keywords = [];
            foreach($res as $oids => $odata) {
                foreach($odata as $name => $value) {
                    if(!is_array($value)) $value = (array) $value;
                    foreach($value as $key => $str) {
                        $keywords = array_merge($keywords, extractKeywords($str));
                    }
                }
            }
         
            // compose list of hash-codes to query the database
            $hash_list = [];
            // we have all words related to the question :
            $db = $om->getDBHandler();
            // make sure all words are in the index
            foreach($keywords as $keyword) {
                // get a 64-bits unsigned integer hash from keyword
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
            $res = $db->sendQuery("SELECT id FROM `resiway_index` WHERE hash in ('".implode("','", $hash_list)."');");
            $index_ids = [];
            while($row = $db->fetchArray($res)) {
                $index_ids[] = $row['id'];
            }

            // add them to the index for given question
            $index_values = array_map(function ($a) use($id) { return [$id, $a]; }, $index_ids);  
            $db->addRecords('resiway_rel_index_question', ['question_id', 'index_id'], $index_values);
            
            // update question indexed status
            $om->write('resiexchange\Question', $id, ['indexed' => true]);  
        }
    }
}
catch(Exception $e) {
    $result = $e->getCode();
    $error_message_ids = array($e->getMessage());
}

// send json result
echo json_encode([
        'result'            => $result, 
        'error_message_ids' => $error_message_ids
    ], 
    JSON_PRETTY_PRINT);