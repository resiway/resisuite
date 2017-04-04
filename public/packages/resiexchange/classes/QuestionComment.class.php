<?php
namespace resiexchange;

class QuestionComment extends \easyobject\orm\Object {

    public static function getColumns() {
        return array(                        
            /* text of the comment */
            'content'			=> array('type' => 'short_text'),
            
            'question_id'       => array('type' => 'many2one', 'foreign_object' => 'resiexchange\Question'),
            
            'score'             => array('type' => 'integer'),
            
            'count_flags'       => array('type' => 'integer')            
        );
    }
    
    public static function getDefaults() {
        return array(        
             'score'            => function() { return 0; },
             'count_flags'      => function() { return 0; },             
        );
    }
    
}