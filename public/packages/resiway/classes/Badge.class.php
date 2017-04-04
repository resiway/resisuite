<?php
namespace resiway;

/**
*
*/
class Badge extends \easyobject\orm\Object {

    public static function getColumns() {
        return array(
            'name'			    => array('type' => 'string', 'multilang' => true),
            
            'description'       => array('type' => 'string', 'multilang' => true),

            /* human-readable unique identifier*/
            'code'			    => array('type' => 'string'),
            
            /* level of badge : 1, 2 or 3 - for bronze, silver, gold / badge_1, badge_2, badge_3 */
            'type'              => array('type' => 'integer'),

            /* identifier for grouping badges inside a same category following steps logic (1,2,3) */
            'group'              => array('type' => 'integer'),
            
            /* category of badge (used for grouping badges that belong to the same area of actions) */
            'category_id'		=> array('type' => 'many2one', 'foreign_object' => 'resiway\BadgeCategory'),            
            
            'count_awarded'     => array('type' => 'integer'),
            
            /* list of actions that might trigger badge attribution */
            'actions_ids'	    => array(
                                    'type' 			    => 'many2many', 
                                    'foreign_object'	=> 'resiway\Action', 
                                    'foreign_field'		=> 'badges_ids', 
                                    'rel_table'		    => 'resiway_rel_action_badge', 
                                    'rel_foreign_key'	=> 'action_id', 
                                    'rel_local_key'		=> 'badge_id'
                                   ),
                                   
            /* list of users having earned the badge */
            'users_ids'	        => array(
                                    'type' 			    => 'many2many', 
                                    'foreign_object'	=> 'resiway\Badge', 
                                    'foreign_field'		=> 'badges_ids', 
                                    // use UserBadge class as relation table
                                    'rel_table'		    => 'resiway_userbadge', 
                                    'rel_foreign_key'	=> 'user_id', 
                                    'rel_local_key'		=> 'badge_id'
                                    ),                                   
        );
    }
    

    public static function getDefaults() {
        return array(
             'count_awarded'      => function() { return 0; }
        );
    }
    
    /*
    This method defines how badges are granted.
    Badges codes in the DB table 'resiway_badge' must match those listed below.
    (badges and actions must be linked in table resiway_rel_action_badge)
    
    Returned values are percentages of achivement (from 0 to 1)
    */
    public static function computeBadge($om, $badge, $uid) {
        // At this point $uid has been verified and read method will return a user object
        switch($badge) {

        /* 
        * Questions 
        */
        case 'curious':
            $res = $om->search('resiexchange\Question', ['creator', '=', $uid]);
            return (float) (count($res)/1);

        case 'inquisitive':
            $res = $om->search('resiexchange\Question', [['creator', '=', $uid], ['score', '>', '0']]);
            return (float) (count($res)/5);          

        case 'socratic':
            $res = $om->search('resiexchange\Question', [['creator', '=', $uid], ['score', '>', '0']]);
            return (float) (count($res)/25);          

                
        case 'favorite_question':
            $res = $om->search('resiexchange\Question', [['creator', '=', $uid], ['count_stars', '>=', '2']]);
            return (float) (count($res)/1);      

        case 'stellar_question':
            $res = $om->search('resiexchange\Question', [['creator', '=', $uid], ['count_stars', '>=', '10']]);
            return (float) (count($res)/1);      

        case 'universal_question':
            $res = $om->search('resiexchange\Question', [['creator', '=', $uid], ['count_stars', '>=', '25']]);
            return (float) (count($res)/1);      
            

        case 'nice_question':
            $res = $om->search('resiexchange\Question', [['creator', '=', $uid], ['score', '>=', '2']]);
            return (float) (count($res)/1);                

        case 'good_question':
            $res = $om->search('resiexchange\Question', [['creator', '=', $uid], ['score', '>=', '5']]);
            return (float) (count($res)/1);                

        case 'great_question':
            $res = $om->search('resiexchange\Question', [['creator', '=', $uid], ['score', '>=', '10']]);
            return (float) (count($res)/1);                

            
        case 'popular_question':
            $res = $om->search('resiexchange\Question', [['creator', '=', $uid], ['count_views', '>=', '100']]);
            return (float) (count($res)/1); 

        case 'famous_question':
            $res = $om->search('resiexchange\Question', [['creator', '=', $uid], ['count_views', '>=', '250']]);
            return (float) (count($res)/1); 

        case 'legendary_question':
            $res = $om->search('resiexchange\Question', [['creator', '=', $uid], ['count_views', '>=', '500']]);
            return (float) (count($res)/1); 
            

            
        /* 
        * Answers 
        */
        case 'answerer':
            $res = $om->search('resiexchange\Answer', ['creator', '=', $uid]);
            return (float) (count($res)/1);
            
        case 'lecturer':
            $res = $om->search('resiexchange\Answer', [['creator', '=', $uid], ['score', '>', '0']]);
            return (float) (count($res)/5);             

        case 'preacher':
            $res = $om->search('resiexchange\Answer', [['creator', '=', $uid], ['score', '>', '0']]);
            return (float) (count($res)/25);             


        case 'nice_answer':
            $res = $om->search('resiexchange\Answer', [['creator', '=', $uid], ['score', '>=', '2']]);
            return (float) (count($res)/1);

        case 'good_answer':
            $res = $om->search('resiexchange\Answer', [['creator', '=', $uid], ['score', '>=', '5']]);
            return (float) (count($res)/1);

        case 'great_answer':
            $res = $om->search('resiexchange\Answer', [['creator', '=', $uid], ['score', '>=', '10']]);
            return (float) (count($res)/1);
            

        case 'popular_answer':
            $res = $om->search('resiexchange\Answer', [['creator', '=', $uid], ['score', '>=', '25']]);
            return (float) (count($res)/1);

        case 'famous_answer':
            $res = $om->search('resiexchange\Answer', [['creator', '=', $uid], ['score', '>=', '50']]);
            return (float) (count($res)/1);

        case 'legendary_answer':
            $res = $om->search('resiexchange\Answer', [['creator', '=', $uid], ['score', '>=', '100']]);
            return (float) (count($res)/1);
            

        case 'inspired':
            $answers_ids = $om->search('resiexchange\Answer', ['creator', '=', $uid]);
            $res = $om->read('resiexchange\Answer', $answers_ids, ['question_id']);
            $res = array_map(function($a){return $a['question_id'];}, $res);
            if(count($res)) {
                $res = $om->search('resiexchange\Question', [['id', 'in', $res], ['count_answers', '=', 1]]);
            }
            return (float) (count($res)/1);

        case 'enlightened':
            $answers_ids = $om->search('resiexchange\Answer', ['creator', '=', $uid]);
            $res = $om->read('resiexchange\Answer', $answers_ids, ['question_id']);
            $res = array_map(function($a){return $a['question_id'];}, $res);
            if(count($res)) {            
                $res = $om->search('resiexchange\Question', [['id', 'in', $res], ['count_answers', '=', 1], ['score', '>=', '5']]);
            }
            return (float) (count($res)/1);

        case 'savior':
            $answers_ids = $om->search('resiexchange\Answer', ['creator', '=', $uid]);
            $res = $om->read('resiexchange\Answer', $answers_ids, ['question_id']);
            $res = array_map(function($a){return $a['question_id'];}, $res);
            if(count($res)) {
                $res = $om->search('resiexchange\Question', [['id', 'in', $res], ['count_answers', '=', 1], ['score', '>=', '10']]);
            }
            return (float) (count($res)/1);            
            

                       
        /* 
        * Participation 
        */            
        case 'verified_human':
            $users = $om->read('resiway\User', $uid, ['verified']);
            return (float) $users[$uid]['verified'];        
            
        case 'autobiographer':
            $required_fields = ['login', 'firstname', 'lastname', 'language', 'country', 'location', 'about'];
            $users = $om->read('resiway\User', $uid, $required_fields);
            // login is always filled, so $count minimum value is 1 (no division by 0)
            $count = 0; 
            $filled = 0;
            foreach($users[$uid] as $field => $value) {
                if(strlen($value) > 0) ++$filled;
                ++$count;
            }
            return (float) ($filled/$count);

        case 'photogenic':
            $users = $om->read('resiway\User', $uid, ['avatar_url']);            
            return (float) (strpos($users[$uid]['avatar_url'], 'identicon') === false);            
            
  
            
        default: break;
        
        }
        
        return (float) 0;
    }

}