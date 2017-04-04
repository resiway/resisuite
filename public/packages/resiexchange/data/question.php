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
    'description'	=>	"Returns a fully-loaded question object",
    'params' 		=>	array(                                         
                        'id'	        => array(
                                            'description' => 'Identifier of the question to retrieve.',
                                            'type' => 'integer', 
                                            'required'=> true
                                            ),                                            
                        )
	)
);


list($result, $error_message_ids) = [true, []];

list($question_id) = [
    $params['id']
];

try {
    
    $om = &ObjectManager::getInstance();

    // 0) retrieve parameters
    $user_id = ResiAPI::userId();
    if($user_id < 0) throw new Exception("request_failed", QN_ERROR_UNKNOWN);
    
    
    // 1) check rights  
    // everyone has read access over all quetions
    
    // 2) action limitations
    // no limitation
    
    // no concurrent action
    
    // retrieve question    
    $result = [];
    $res = $om->read('resiexchange\Question', $question_id, ['id', 'creator', 'created', 'editor', 'edited', 'modified', 'title', 'title_url', 'content', 'count_stars', 'count_views', 'count_votes', 'score', 'categories_ids', 'answers_ids', 'comments_ids']);
    if($res < 0 || !isset($res[$question_id])) throw new Exception("question_unknown", QN_ERROR_INVALID_PARAM);
    $question_data = $res[$question_id];

    $result = $question_data;

    
    // retreive author data
    $author_data = ResiAPI::loadUserPublic($question_data['creator']);
    if($author_data < 0) throw new Exception("question_author_unknown", QN_ERROR_UNKNOWN_OBJECT);
    $result['creator'] = $author_data;

    // retrieve eiditor data
    if($question_data['editor'] > 0) {
        $editor_data = ResiAPI::loadUserPublic($question_data['editor']);
        if($editor_data < 0) throw new Exception("question_editor_unknown", QN_ERROR_UNKNOWN_OBJECT);        
        $result['editor'] = $editor_data;
    }    
      
    // retrieve actions performed by the user on this question
    $question_history = ResiAPI::retrieveHistory($user_id, 'resiexchange\Question', $question_id);
    $result['history'] = $question_history[$question_id];

    
    if($user_id > 0 && !isset($result['history']['resiexchange_question_view'])) {
        // update question's count_views 
        $om->write('resiexchange\Question', $question_id, [ 'count_views' => $question_data['count_views']+1 ]);
        // add question view to user history
        ResiAPI::registerAction($user_id, 'resiexchange_question_view', 'resiexchange\Question', $question_id);  
    }
    
    // retrieve tags
    $result['tags'] = [];
    $res = $om->read('resiway\Category', $question_data['categories_ids'], ['title', 'description', 'path', 'parent_path']);        
    if($res > 0) {
        $tags = [];
        foreach($res as $tag_id => $tag_data) {           
            $tags[$tag_id] = array(
                                        'id'            => $tag_id,
                                        'title'         => $tag_data['title'], 
                                        'description'   => $tag_data['description'],                                         
                                        'path'          => $tag_data['path'],
                                        'parent_path'   => $tag_data['parent_path']
                                    );
        }      
        
        // asign resulting array to returned value
        $result['tags'] = array_values($tags);
    }

    // retrieve comments
    // output JSON type has to be Array
    $result['comments'] = [];
    $res = $om->read('resiexchange\QuestionComment', $question_data['comments_ids'], ['creator', 'created', 'content', 'score']);        
    if($res > 0) {
        // memorize comments authors identifiers for later load
        $comments_authors_ids = [];
        $comments = [];
        foreach($res as $comment_id => $comment_data) {
            $comments_authors_ids[] = $comment_data['creator'];
            $comments[$comment_id] = array(
                                        'id'        => $comment_id,
                                        'created'   => $comment_data['created'], 
                                        'content'   => $comment_data['content'], 
                                        'score'     => $comment_data['score']
                                    );
        }
        
        // retrieve comments authors
        $comments_authors = $om->read('resiway\User', $comments_authors_ids, ResiAPI::userPublicFields());        
        if($comments_authors > 0) {
            foreach($res as $comment_id => $comment_data) {
                $author_id = $comment_data['creator'];
                $comments[$comment_id]['creator'] = $comments_authors[$author_id];
            }
        }
        
        // retrieve actions performed by the user on these comments
        $comments_history = ResiAPI::retrieveHistory($user_id, 'resiexchange\QuestionComment', $question_data['comments_ids']);
        foreach($comments_history as $comment_id => $history) {
            $comments[$comment_id]['history'] = $history;
        }        
        
        // asign resulting array to returned value
        $result['comments'] = array_values($comments);
    }
    
    // retreive answers
    // output JSON type has to be Array
    $result['answers'] = [];
    $res = $om->read('resiexchange\Answer', $question_data['answers_ids'], ['creator', 'created', 'editor', 'edited', 'content', 'content_excerpt', 'score', 'comments_ids']);    
    if($res > 0) {
        // memorize answers authors identifiers for later load
        $answers_authors_ids = [];
        // memorize answers comments identifiers for later load
        $answers_comments_ids = [];
        $answers = [];
        foreach($res as $answer_id => $answer_data) {
            $answers_authors_ids[] = $answer_data['creator'];
            $answers_comments_ids = array_merge($answers_comments_ids, $answer_data['comments_ids']);
            $answers[$answer_id] = array(
                                    'id'                => $answer_id,
                                    'created'           => $answer_data['created'], 
                                    'content'           => $answer_data['content'], 
                                    'content_excerpt'   => $answer_data['content_excerpt'],                                     
                                    'score'             => $answer_data['score'],
                                    'comments_ids'      => $answer_data['comments_ids'],
                                    'comments'          => [],
                                    'history'           => []);
        }

        // retrieve answers authors
        $answers_authors = $om->read('resiway\User', $answers_authors_ids, ResiAPI::userPublicFields());        
        if($answers_authors > 0) {
            foreach($res as $answer_id => $answer_data) {
                $author_id = $answer_data['creator'];
                $answers[$answer_id]['creator'] = $answers_authors[$author_id];
            }
        }        
        
        // retrieve actions performed by the user on these answers
        $answers_history = ResiAPI::retrieveHistory($user_id, 'resiexchange\Answer', $question_data['answers_ids']);
        foreach($answers_history as $answer_id => $history) {
            $answers[$answer_id]['history'] = $history;
        }
        
        
        // retrieve answers comments
        $res = $om->read('resiexchange\AnswerComment', $answers_comments_ids, ['answer_id', 'creator', 'created', 'content', 'score']);        
        if($res > 0) {
            // memorize comments authors identifiers for later load
            $comments_authors_ids = [];            

            foreach($answers as $answer_id => $answer_data) {
                foreach($answer_data['comments_ids'] as $comment_id) {
                    $comment_data = $res[$comment_id];
                    $comments_authors_ids[] = $comment_data['creator'];
                    $answers[$answer_id]['comments'][$comment_id] = array(
                                    'id'        => $comment_id,
                                    'created'   => $comment_data['created'], 
                                    'content'   => $comment_data['content'], 
                                    'score'     => $comment_data['score'],
                                    'history'   => []);                       
                }
            }

            // retrieve comments authors
            $comments_authors = $om->read('resiway\User', $comments_authors_ids, ResiAPI::userPublicFields());        
            if($comments_authors > 0) {                    
                foreach($answers as $answer_id => $answer_data) {
                    foreach($answer_data['comments_ids'] as $comment_id ) {
                        $comment_data = $res[$comment_id];
                        $author_id = $comment_data['creator'];
                        $answers[$answer_id]['comments'][$comment_id]['creator'] = $comments_authors[$author_id];
                    }                                       
                }
            }
            
            // retrieve actions performed by the user on these comments
            $comments_history = ResiAPI::retrieveHistory($user_id, 'resiexchange\AnswerComment', $answers_comments_ids);
            foreach($answers as $answer_id => $answer_data) {
                foreach($answer_data['comments_ids'] as $comment_id ) {
                    $answers[$answer_id]['comments'][$comment_id]['history'] = $comments_history[$comment_id];
                }
            }
            
            foreach($answers as $answer_id => $answer_data) {
                $answers[$answer_id]['comments'] = array_values($answer_data['comments']);
            }
        }
        // asign resulting array to returned value
        $result['answers'] = array_values($answers);
        
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
                    'error_message_ids' => $error_message_ids
                 ], 
                 JSON_PRETTY_PRINT);