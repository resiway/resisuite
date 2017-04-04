<?php
namespace resiway;


class BadgeCategory extends \easyobject\orm\Object {

    public static function getColumns() {
        return array(
            /* all objects must define a 'name' column (default is id) */
            'name'				=> array('type' => 'alias', 'alias' => 'title'),

           
            'title'             => array('type' => 'string', 'multilang' => true),
            
            'description'		=> array('type' => 'text', 'multilang' => true),
            
            /* order to follow for displaying the categories */
            'order'		        => array('type' => 'integer'),
            
            'badges_ids'		=> array(
                                    'type'              => 'one2many', 
                                    'foreign_object'    => 'resiway\Badge', 
                                    'foreign_field'     => 'category_id', 
                                    'order'             => 'name')
                                   
        );
    }
    
}