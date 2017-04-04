<?php
namespace resiway;


class Repository extends \easyobject\orm\Object {

    public static function getColumns() {
        return array(
            
            'key'	    => array('type' => 'string'),

            'type'	    => array('type' => 'string'),
            
            'value'	    => array('type' => 'binary'),
                                   
        );
    }
    
    public static function getUnique() {
        return array(
            ['key']
        );
    }
}