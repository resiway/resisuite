<?php
namespace resiway;

class Action extends \easyobject\orm\Object {

    public static function getColumns() {
        return array(
            /* short human readable identifier for the action */
            'name'				    => array('type' => 'string'),

            /* short explanation about what the action consists of */
            'description'		    => array('type' => 'text', 'multilang' => true),
            
            /* class of the objects the action applies to */ 
            'object_class'		    => array('type' => 'string'),
                       
            /* minimum reputation required for a user to be allowed to perform the action */
            'required_reputation' 	=> array('type' => 'integer'),
            
            /* amount (positive or negative) by which should be incremented the reputation of the user performing the action */
            'user_increment'    	=> array('type' => 'integer'),
            
            /* amount (positive or negative) by which should be incremented the reputation of the author of the object targeted by the action */
            'author_increment'   	=> array('type' => 'integer'),
            
            /* badges that might be impacted by the action */
            'badges_ids'	        => array(
                                        'type' 			    => 'many2many', 
                                        'foreign_object'	=> 'resiway\Badge', 
                                        'foreign_field'		=> 'actions_ids', 
                                        'rel_table'		    => 'resiway_rel_action_badge', 
                                        'rel_foreign_key'	=> 'badge_id', 
                                        'rel_local_key'		=> 'action_id'
                                       ),             
        );
    }
}
