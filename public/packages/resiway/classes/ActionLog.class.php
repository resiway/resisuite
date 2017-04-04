<?php
namespace resiway;

class ActionLog extends \easyobject\orm\Object {

    public static function getColumns() {
        return array(
            /* all objects must define a 'name' column (default is id) */
            'name'				    => array('type' => 'alias', 'alias' => 'object_name'),

            /* action performed */
            'action_id'				=> array('type' => 'many2one', 'foreign_object'=> 'resiway\Action'),
            
            /* user performing the action */
            'user_id'				=> array('type' => 'many2one', 'foreign_object'=> 'resiway\User'),

            /* user author of targeted object */
            'author_id'				=> array('type' => 'many2one', 'foreign_object'=> 'resiway\User'),

            /* value by which user reputation is updated, if any */ 
            'user_increment'        => array('type' => 'integer'),

            /* value by which author reputation is updated, if any */ 
            'author_increment'      => array('type' => 'integer'),
            
            /* class of the object the action applies to */ 
            'object_class'		    => array('type' => 'string'),
                        
            /* identifier of the object the action applies to */
            'object_id' 	        => array('type' => 'integer'),
            
            'object_name'           => array(
                                        'type'              => 'function',
                                        'result_type'       => 'string', 
                                        'store'             => false,                               
                                        'function'          => 'resiway\ActionLog::getObjectName'
                                    )
        );
    }

    public static function getObjectName($om, $oids, $lang) {
        $result = [];
        $objects_ids = [];
        $res = $om->read('resiway\ActionLog', $oids, ['object_class', 'object_id'], $lang);
        // first pass : collect all object identifiers (grouped by class)
        foreach($res as $oid => $values) {            
            $object_class = $res[$oid]['object_class'];
            $object_id = $res[$oid]['object_id'];
            if( !isset($objects_ids[$object_class]) ) $objects_ids[$object_class] = [];
            $objects_ids[$object_class][] = $object_id;
        }
        // retrieve objects names
        $objects_names = [];
        foreach($objects_ids as $object_class => $ids) {
            $objects_names[$object_class] = $om->read($object_class, $ids, ['name'], $lang);
        }
        // second pass: assign values
        foreach($res as $oid => $values) {            
            $object_class = $res[$oid]['object_class'];
            $object_id = $res[$oid]['object_id'];
            $result[$oid] = $objects_names[$object_class][$object_id]['name'];
        }        
        return $result;        
    }      
}
