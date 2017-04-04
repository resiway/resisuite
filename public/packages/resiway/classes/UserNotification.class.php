<?php
namespace resiway;


class UserNotification extends \easyobject\orm\Object {

    public static function getColumns() {
        return array(
        
            'user_id'        => array('type' => 'many2one', 'foreign_object' => 'resiway\User'),
            
            'title'			 => array('type' => 'string'),    
            
            'content'		 => array('type' => 'html')
                                   
        );
    } 

}