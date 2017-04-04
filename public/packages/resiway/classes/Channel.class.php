<?php
namespace resiway;


class Channel extends \easyobject\orm\Object {

    public static function getColumns() {
        return array(
            /* name of the channel */
            'name'				    => array('type' => 'string', 'multilang' => true),
            
            /* text describing the channel */
            'description'			=> array('type' => 'text', 'multilang' => true),

            /* identifiers of the categories in this channel */ 
            'categories_ids'          => array(
                                        'type'		    => 'one2many', 
                                        'foreign_object'=> 'resiway\Category',
                                        'foreign_field'	=> 'channel_id'
                                        ),
                                        
            /* identifiers of the questions in this channel */ 
            'questions_ids'          => array(
                                        'type'		    => 'one2many', 
                                        'foreign_object'=> 'resiexchange\Question', 
                                        'foreign_field'	=> 'channel_id'
                                        )            
            
        );
    }

      
}
