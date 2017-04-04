<?php
/* resi.api.php - library holding global functions for controllers of the resiway platform.

    This file is part of the resiway program <http://www.github.com/cedricfrancoys/resiway>
    Copyright (C) ResiWay.org, 2017
    Some Right Reserved, GNU GPL 3 license <http://www.gnu.org/licenses/>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3, or (at your option)
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, see <http://www.gnu.org/licenses/>
*/
use easyobject\orm\ObjectManager as ObjectManager;
use easyobject\orm\PersistentDataManager as PersistentDataManager;
use easyobject\orm\DataAdapter as DataAdapter;
use html\HTMLPurifier_Config as HTMLPurifier_Config;
use html\HtmlTemplate as HtmlTemplate;

// these utilities require inclusion of main configuration file 
require_once('qn.lib.php');

// override ORM method for date formatting (ISO 8601)
DataAdapter::setMethod('db', 'orm', 'date', function($value) {
    $dateTime = DateTime::createFromFormat('Y-m-d', $value);
    return date("c", $dateTime->getTimestamp());
});
/*
// override ORM method for datetime formatting (ISO 8601)
DataAdapter::setMethod('db', 'orm', 'datetime', function($value) {
    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    return date("c", $dateTime->getTimestamp());
});
  */

            
class ResiAPI {
    
    public static function getHTMLPurifierConfig() {
        // clean HTML input html
        // strict cleaning: remove non-standard tags and attributes    
        $config = HTMLPurifier_Config::createDefault();
        $config->set('URI.Base',                'http://www.resiway.org/');
        $config->set('URI.MakeAbsolute',        true);                  // make all URLs absolute using the base URL set above
        $config->set('AutoFormat.RemoveEmpty',  true);                  // remove empty elements
        $config->set('HTML.Doctype',            'XHTML 1.0 Strict');    // valid XML output
        $config->set('CSS.AllowedProperties',   []);                    // remove all CSS
        // allow only tags and attributes that match most lightweight markup language 
        $config->set('HTML.AllowedElements',    array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'hr', 'pre', 'a', 'img', 'br', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'ul', 'ol', 'li', 'strong', 'b', 'i', 'code', 'blockquote'));
        $config->set('HTML.AllowedAttributes',  array('a.href', 'img.src', 'img.alt', 'table.summary', 'td.abbr', 'th.abbr'));
        return $config;
    }
    
    /**
    * Retrieves resiway-app current revision identifier.
    *
    * @return   string
    */
    public static function currentRevision() {
        $file = trim(explode(' ', file_get_contents('../.git/HEAD'))[1]);
        $hash = substr(file_get_contents("../.git/$file"), 0, 7);
        $time = filemtime ('../.git/index');
        $date = date("Y.m.d", $time);
        return "$date.$hash";
    }

    // add a message to the spool
    public static function spool($user_id, $subject, $body) {
// todo : script run by cron to send emails every ? minutes        
        // message files format is: 11 digits (user unique identifier) with 3 digits extension in case of multiple files
        $temp = sprintf("%011d", $user_id);
        $filename = $temp;
        $i = 0;
        while(file_exists(EMAIL_SPOOL_DIR."/{$filename}")) {
            $filename = sprintf("%s.%03d", $temp, ++$i);
        }
        // data consists of parsed template and subject (JSON formatted)
        return file_put_contents(EMAIL_SPOOL_DIR."/$filename", json_encode(array("subject" => $subject, "body" => $body), JSON_PRETTY_PRINT));
    }
        
    public static function credentialsDecode($code) {
        // convert base64url to base64
        $code = str_replace(['-', '_'], ['+','/'], $code);
        return explode(';', base64_decode($code));
    }
    
    public static function credentialsEncode($login, $password) {
        $code = base64_encode($login.";".$password);
        // convert base64 to url safe-encoded
        return str_replace(['+','/'],['-', '_'], $code);
    }
    
// todo: complete
    public static function makeLink($object_class, $object_id) {
        $link = '';
        switch($object_class) {
            case 'resiway\User':                    
                return '#/user/'.$object_id;
                
            case 'resiway\Category':                
                return '#/category/'.$object_id;
                
            case 'resiway\Badge':    
            
            case 'resiexchange\Question':          
                return '#/question/'.$object_id;
                
            case 'resiexchange\Answer':
                $om = &ObjectManager::getInstance(); 
                $res = $om->read($object_class, $object_id, ['question_id']);
                return '#/question/'.$res[$object_id]['question_id'];
                
            case 'resiexchange\QuestionComment':
                $om = &ObjectManager::getInstance(); 
                $res = $om->read($object_class, $object_id, ['question_id']);
                return '#/question/'.$res[$object_id]['question_id'];
                
            case 'resiexchange\AnswerComment':                        
                $om = &ObjectManager::getInstance(); 
                $res = $om->read($object_class, $object_id, ['answer_id.question_id']);
                return '#/question/'.$res[$object_id]['answer_id.question_id'];            
        }
        return $link;
    }

    public static function userSign($login, $password) {
        $om = &ObjectManager::getInstance();        
        $errors = $om->validate('resiway\User', ['login' => $login, 'password' => $password]);
        if(count($errors)) return QN_ERROR_INVALID_PARAM;
        $ids = $om->search('resiway\User', [['login', '=', $login], ['password', '=', $password]]);
        if($ids < 0 || !count($ids)) return QN_ERROR_INVALID_PARAM;
        $user_id = $ids[0];
        // update 'last_login' field
        $om->write('resiway\User', $user_id, [ 'last_login' => date("Y-m-d H:i:s") ]);        
        $pdm = &PersistentDataManager::getInstance();
        return $pdm->set('user_id', $user_id);                
    }
    
    /**
    * Retrieves current user identifier.
    * If user is not logged in, returns 0 (GUEST_USER_ID)
    *
    * @return   integer
    */
    public static function userId() {
        $pdm = &PersistentDataManager::getInstance();
        return $pdm->get('user_id', 0);
    }

   
    /**
    * Resolve given action name to its related object identifier.
    * If action is unknown, returns a negative value (QN_ERROR_INVALID_PARAM)
    *
    * @param    string  $action_name    name of the action to resolve
    * @return   integer 
    */
    public static function actionId($action_name) {
        static $actionsTable = [];
        
        if(!isset($actionTable[$action_name])) {        
            $om = &ObjectManager::getInstance();            
            $res = $om->search('resiway\Action', ['name', '=', $action_name]);
            if($res < 0 || !count($res)) return QN_ERROR_INVALID_PARAM;
            $actionTable[$action_name] = $res[0];
        }
        return $actionTable[$action_name];
    }

    
    /**
    * Provides an array holding fields names holding public information
    * This array is used n order to determine which data is public.
    *
    */
    public static function userPublicFields() {
        return ['id', 
                'created',
                'verified',
                'last_login',
                'display_name',
                'avatar_url',
                'about',
                'language', 
                'country', 
                'location',                
                'reputation',
                'role',
                'count_questions', 
                'count_views', 
                'count_answers', 
                'count_comments',              
                'count_badges_1', 
                'count_badges_2', 
                'count_badges_3'
               ];
    }

    public static function userPrivateFields() {
        return ['login', 
                'firstname',
                'lastname', 
                'publicity_mode',
                'notifications_ids',
                'notify_reputation_update', 
                'notify_badge_awarded', 
                'notify_question_comment', 
                'notify_answer_comment', 
                'notify_question_answer'               
               ];
    }
    
    /**
    *
    * to maintain a low load-time, this method should be used only when a single user object is requested 
    */
    public static function loadUserPublic($user_id) {
        // check params consistency
        if($user_id <= 0) return QN_ERROR_INVALID_PARAM;        
        
        $om = &ObjectManager::getInstance();        
        
        $res = $om->read('resiway\User', $user_id, self::userPublicFields() );        
        if($res < 0 || !isset($res[$user_id])) return QN_ERROR_UNKNOWN_OBJECT;    
        return $res[$user_id];        
    }

    /**
    *
    * to maintain a low load-time, this method should be used only when a single user object is requested 
    */
    public static function loadUserPrivate($user_id) {
        // check params consistency
        if($user_id <= 0) return QN_ERROR_INVALID_PARAM;        
        
        $om = &ObjectManager::getInstance();        
        
        $res = $om->read('resiway\User', $user_id, array_merge(self::userPrivateFields(), self::userPublicFields()) );
        if($res < 0 || !isset($res[$user_id])) return QN_ERROR_UNKNOWN_OBJECT;    
        return $res[$user_id];        
    }    

    /**
    * returns an associative array holding keys-values of the records having key matching given mask
    */
    public static function repositoryGet($key_mask) {
        $result = [];
        $om = &ObjectManager::getInstance(); 
        $db = $om->getDBHandler();
        $res = $db->sendQuery("SELECT `key`, `value` FROM `resiway_repository` WHERE `key` like '$key_mask';");
        while ($row = $db->fetchArray($res)) {
            $result[$row['key']] = $row['value'];
        }
        return $result;
    }

    public static function repositorySet($key, $value) {
        $om = &ObjectManager::getInstance(); 
        $db = $om->getDBHandler();      
        $db->sendQuery("UPDATE `resiway_repository` SET `value` = '$value' WHERE `key` like '$key_mask';");        
    }

    /*
    * increments by one the value of the records having key matching given mask
    */
    public static function repositoryInc($key_mask) {
        $om = &ObjectManager::getInstance(); 
        $db = $om->getDBHandler();       
        $db->sendQuery("UPDATE `resiway_repository` SET `value` = `value`+1 WHERE `key` like '$key_mask';");
    }

    /*
    * decrements by one the value of the records having key matching given mask
    */
    public static function repositoryDec($key_mask) {
        $om = &ObjectManager::getInstance(); 
        $db = $om->getDBHandler();       
        $db->sendQuery("UPDATE `resiway_repository` SET `value` = `value`-1 WHERE `key` like '$key_mask';");
    }
    

    /*
    * @param integer    $user_id   identifier of the user to which notification is addressed
    * @param string     $type      type of notification (reputation_update, badge_awarded, question_answered, question_commented, answer_commented)
    * @param string     $title     short title describing the notice
    * @param string     $content   html to be displayed (whatever the media)
    */
    private static function userNotify($user_id, $type, $notification) {
        $om = &ObjectManager::getInstance();
        $user_data = self::loadUserPrivate($user_id);
        // if notification has to be sent by email, store message in spool
        if(isset($user_data['notify_'.$type]) && $user_data['notify_'.$type]) {
            // append a notice to all mails sent by resiway
            $email_notice = self::getUserNotification('mail_notice', $user_data['language'], ['user'=>$user_data]);
            self::spool($user_id, 'ResiWay - '.$notification['subject'], $notification['body'].$email_notice['body']);  
        }
        // in case we decide to send emails, here is the place to add something to user queue
        $notification_id = $om->create('resiway\UserNotification', [  
            'user_id'   => $user_id, 
            'title'     => $notification['subject'], 
            'content'   => $notification['body']
        ]);
        // update notifications array for current session
        // we'll need to be able to provide js-client with pending notifications
        if(self::userId() == $user_id) {
            $pdm = &PersistentDataManager::getInstance();
            $pdm->set('notifications', array_merge($pdm->get('notifications', []), [$notification_id]) ); 
        }        
        // return identifier of the newly created notification
        return $notification_id;
    }
    
    /*
    *
    * $data is expected to be an array holding a 'user' entry with, at least, a 'id' index
    */
    private static function getUserNotification($template_id, $lang, $data) {
        $om = &ObjectManager::getInstance();

        // subject of the email should be defined in the template, as a <var> tag holding a 'title' attribute
        $subject = '';
        $body = '';
        
        // read template according to user prefered language
        $file = "packages/resiway/i18n/{$lang}/{$template_id}.html";
        if( ($html = @file_get_contents($file, FILE_TEXT)) ) {
            $template = new HtmlTemplate($html, 
                                        // renderer is in charge of resolving vars common to all templates
                                        [
                                        'subject'		    =>	function ($params, $attributes) use (&$subject) {
                                                $subject = $attributes['title'];
                                                return '';
                                        },
                                        'url_object'	    =>	function ($params, $attributes) {
                                                $link = self::makeLink($params['object_class'], $params['object_id']);
                                                return "<a href=\"http://www.resiway.org/resiexchange.fr{$link}\">{$attributes['title']}</a>";
                                        },                                                        
                                        'url_profile'	    =>	function ($params, $attributes) {
                                                return "<a href=\"http://www.resiway.org/resiexchange.fr#/user/profile/{$params['user']['id']}\">{$attributes['title']}</a>";
                                        },
                                        'url_profile_edit'	=>	function ($params, $attributes) {
                                                return "<a href=\"http://www.resiway.org/resiexchange.fr#/user/edit/{$params['user']['id']}\">{$attributes['title']}</a>";
                                        }                                        
                                        ], 
                                        // remaining data is given in the $data parameter
                                        $data);
            // parse template as html
            $body = $template->getHtml();
        }
        return array("subject" => $subject, "body" => $body);
    }
    
    /**
    * Reflects performed action on user's and object author's reputations
    * by action increment or its opposite, according to $sign parameter
    *
    * @param    integer  $user_id       identifier of the user performing the action
    * @param    integer  $action_id     identifier of the action being performed
    * @param    string   $object_class  class of the targeted object (ex. 'resiexchange\Question')
    * @param    integer  $object_id     identifier of the object on which action is performed
    * @param    integer  $sign          +1 for incrementing reputation, -1 to decrement it
    * @return   array    returns an array holding user and author respective ids and increments
    */      
    private static function impactReputation($user_id, $action_id, $object_class, $object_id, $sign=1) {
        $result = ['user' => ['id' => $user_id, 'increment' => 0], 'author' => ['id' => 0, 'increment' => 0]];
        
        $om = &ObjectManager::getInstance();

        // retrieve author identifier
        $res = $om->read($object_class, $object_id, ['creator']);
        if($res > 0 && isset($res[$object_id])) {
            $result['author']['id'] = $res[$object_id]['creator'];
        }
        
        // retrieve action data
        $res = $om->read('resiway\Action', $action_id, ['name', 'user_increment', 'author_increment']);
        if($res > 0 && isset($res[$action_id])) {
            $action_data = $res[$action_id];
            
            if($action_data['user_increment'] != 0) {
                // retrieve user data
                $res = $om->read('resiway\User', $user_id, ['verified', 'reputation']);
                if($res > 0 && isset($res[$user_id])) {
                    $user_data = $res[$user_id];
                    // prevent reputation update for non-verified users
                    if($user_data['verified']) {
                        $result['user']['increment'] = $sign*$action_data['user_increment'];
                        $om->write('resiway\User', $user_id, array('reputation' => $user_data['reputation']+$result['user']['increment']));
                    }
                }
            }
            
            if($action_data['author_increment'] != 0) {
                // retrieve author data (creator of targeted object)
                $author_id = $result['author']['id'];
                $res = $om->read('resiway\User', $author_id, ['verified', 'reputation']);        
                if($res > 0 && isset($res[$author_id])) {    
                    $author_data = $res[$author_id];            
                    // prevent reputation update for non-verified users
                    if($author_data['verified']) {                    
                        $result['author']['increment'] = $sign*$action_data['author_increment'];            
                        $om->write('resiway\User', $author_id, array('reputation' => $author_data['reputation']+$result['author']['increment']));
                    }
                }

            }
        }
        return $result;
    }
    

    /**
    * Tells if given user is allowed to perform given action.
    * If given user or action is unknown, returns false
    *
    * @param    integer  $user_id       identifier of the user performing the action
    * @param    integer  $action_id     identifier of the action being performed
    * @param    string   $object_class  class of the targeted object (ex. 'resiexchange\Question')
    * @param    integer  $object_id     identifier of the object on which action is performed    
    * @return   boolean 
    */    
    public static function isActionAllowed($user_id, $action_id, $object_class, $object_id) {
        // check params consistency
        if($user_id <= 0 || $action_id <= 0) return false;
       
        $om = &ObjectManager::getInstance();

        if($object_id > 0) {
            // retrieve object data 
            $res = $om->read($object_class, $object_id, ['id', 'creator']);
            if($res < 0 || !isset($res[$object_id])) return false;
            
            // unless specified in action limitations, all actions are granted on an object owner
            if($user_id == $res[$object_id]['creator']) return true;
            // users have full access on their own profile
            if($object_class == 'resiway\User' && $user_id == $object_id) return true;
        }
        
        // read user data
        $res = $om->read('resiway\User', $user_id, ['reputation', 'role']);        
        if($res < 0 || !isset($res[$user_id])) return false;
        $user_data = $res[$user_id];
        
        // all actions are granted to admin users
        if($user_data['role'] == 'a') return true;

// todo : deal with moderators permissions
        
        // read action data (as this is the first call in the action proccessing logic, 
        // we take advantage of this call to load all fields that will be required at some point
        $res = $om->read('resiway\Action', $action_id, ['required_reputation', 'user_increment', 'author_increment']);
        if($res < 0 || !isset($res[$action_id])) return false;   
        $action_data = $res[$action_id];
        
        // check objects consistency
        if(!isset($action_data['required_reputation']) || !isset($user_data['reputation'])) return false;
        
        // check user reputation against minimum reputation required to perform the action
        if($user_data['reputation'] >= $action_data['required_reputation']) return true;

        return false;
    }
    
    /**
    * Tells if an action has already been performed by given user on specified object.
    *
    * @param    integer  $user_id       identifier of the user performing the action
    * @param    mixed    $action        action name or identifier of the action being performed
    * @param    string   $object_class  class of the targeted object (ex. 'resiexchange\Question')
    * @param    integer  $object_id     identifier of the object on which action is performed
    * @return   boolean
    */
    public static function isActionRegistered($user_id, $action, $object_class, $object_id) {
        
        // retrieve action object identifier
        $action_id = intval($action);
        if($action_id == 0) {
            // if we received a string, try to resolve action name
            $action_id = self::actionId($action);            
        }
        
        // check params consistency
        if($user_id <= 0 || $action_id <= 0) return false;
             
        $om = &ObjectManager::getInstance();
        
        $res = $om->search('resiway\ActionLog', [
                    ['user_id',     '=', $user_id], 
                    ['action_id',   '=', $action_id], 
                    ['object_class','=', $object_class], 
                    ['object_id',   '=', $object_id]
               ]);
        if($res < 0 || !count($res)) return false;
        
        return true;
    }
    
    /**
    * Logs the action being performed by given user on specified object.
    *
    * @param    integer  $user_id       identifier of the user performing the action
    * @param    integer  $action_id     identifier of the action being performed
    * @param    string   $object_class  class of the targeted object (ex. 'resiexchange\Question')
    * @param    integer  $object_id     identifier of the object on which action is performed
    * @return   boolean  returns true if operation succeeds, false otherwise.   
    */
    public static function registerAction($user_id, $action_name, $object_class, $object_id) {        
        // retrieve action identifier
        $action_id = self::actionId($action_name);

        // check params consistency
        if($user_id <= 0 || $action_id <= 0 || $object_id <= 0) return false;
        
        $impact = self::impactReputation($user_id, $action_id, $object_class, $object_id, 1);
        $author_id = $impact['author']['id'];
        
        $om = &ObjectManager::getInstance();
        
        // register action
        $actionlog_id = $om->create('resiway\ActionLog', [
                        'user_id'               => $user_id,
                        'author_id'             => $author_id,
                        'action_id'             => $action_id, 
                        'object_class'          => $object_class, 
                        'object_id'             => $object_id,
                        'user_increment'        => $impact['user']['increment'],
                        'author_increment'      => $impact['author']['increment']
                       ]);

        // update current user's pending actions list
        // we'll need to be able to provide js-client with pending actions (for badges update)        
        $pdm = &PersistentDataManager::getInstance();
        $pdm->set('actions', array_merge($pdm->get('actions', []), [$actionlog_id]));
        
        // handle notifications
        $user_data = self::loadUserPrivate($user_id);
        $author_data = self::loadUserPrivate($author_id);    

        $res = $om->read($object_class, $object_id, ['id', 'name']);
        $object = $res[$object_id];
        
        // build array that will hold the data for the message
        $data = [
            'user'          => $user_data,
            'author'        => $author_data,
            'object'        => $object,
            'object_id'     => $object_id,
            'object_class'  => $object_class            
        ];        
                
        // handle notifiable actions 
        switch($action_name) {
        case 'resiexchange_question_answer':
            $notification = self::getUserNotification('notification_question_answer', $author_data['language'], $data);
            self::userNotify($author_id, 'question_answer', $notification);
            break;
        case 'resiexchange_question_comment':
            $notification = self::getUserNotification('notification_question_comment', $author_data['language'], $data);
            self::userNotify($author_id, 'question_comment', $notification);        
            break;
        case 'resiexchange_answer_comment':
            $notification = self::getUserNotification('notification_answer_comment', $author_data['language'], $data);
            self::userNotify($author_id, 'answer_comment', $notification);        
            break;
        }
        
        // notify users if there is any reputation change 
        if($impact['user']['increment'] != 0) {
            $data['reputation_increment'] = sprintf("%+d", $impact['user']['increment']);
            $notification = self::getUserNotification('notification_reputation_update', $user_data['language'], $data);
            self::userNotify($user_id, 'reputation_update', $notification);            
        }
        if($impact['author']['increment'] != 0) {
            $data['user'] = $author_data;
            $data['reputation_increment'] = sprintf("%+d", $impact['author']['increment']);
            self::userNotify( $author_id, 
                              'reputation_update', 
                              self::getUserNotification('notification_reputation_update', $author_data['language'], $data)
                             );
                              
        }        
        return true;
    }

     
    /**
    * Removes latest record of the given action from the log.
    *
    * @param    integer  $user_id       identifier of the user performing the action
    * @param    integer  $action_id     identifier of the action being performed
    * @param    string   $object_class  class of the targeted object (ex. 'resiexchange\Question')
    * @param    integer  $object_id     identifier of the object on which action is performed
    * @return   boolean  returns true if operation succeeds, false otherwise.
    */
    public static function unregisterAction($user_id, $action_name, $object_class, $object_id) {
        // retrieve action identifier
        $action_id = self::actionId($action_name);
        
        // check params consistency
        if($user_id <= 0 || $action_id <= 0 || $object_id <= 0) return false;
        
        $impact = self::impactReputation($user_id, $action_id, $object_class, $object_id, -1);
        $author_id = $impact['author']['id'];
        
        $om = &ObjectManager::getInstance();

        $log_ids = $om->search('resiway\ActionLog', [
                                    ['user_id',      '=', $user_id], 
                                    ['action_id',    '=', $action_id], 
                                    ['object_class', '=', $object_class], 
                                    ['object_id',    '=', $object_id]
                                ], 'created', 'desc');
                   
        if($log_ids < 0 || !count($log_ids)) return;
        
        $res = $om->remove('resiway\ActionLog', $log_ids, true);        

        // handle notifications
        $user_data = self::loadUserPrivate($user_id);
        $author_data = self::loadUserPrivate($author_id);    
       
        // build array that will hold the data for the message
        $data = [
            'user'          => $user_data,
            'author'        => $author_data
        ];
        
        // notify users if there is any reputation change 
        if($impact['user']['increment'] != 0) {
            $data['reputation_increment'] = sprintf("%+d", $impact['user']['increment']);
            $notification = self::getUserNotification('notification_reputation_update', $user_data['language'], $data);
            self::userNotify($user_id, 'reputation_update', $notification);            
        }
        if($impact['author']['increment'] != 0) {
            $data['user'] = $author_data;
            $data['reputation_increment'] = sprintf("%+d", $impact['author']['increment']);
            $notification = self::getUserNotification('notification_reputation_update', $author_data['language'], $data);            
            self::userNotify($author_id, 'reputation_update', $notification);            
        }
        
        return true;
    }
    
    /**
    * Retrieves history of actions performed by given user on specified object(s)
    *
    * @return array list of action names (ex.: resiexchange_question_votedown, resiexchange_comment_voteup, ...)
    */
    public static function retrieveHistory($user_id, $object_class, $object_ids) {
        $om = &ObjectManager::getInstance();
        
        $history = [];
        if(!is_array($object_ids)) $object_ids = (array) $object_ids;
        // init $history array to prevent returning a null result
        foreach($object_ids as $object_id) $history[$object_id] = [];
        if($user_id > 0 && count($object_ids)) {
            $actions_ids = $om->search('resiway\ActionLog', [
                                ['user_id',     '=', $user_id],
                                ['object_class','=', $object_class], 
                                ['object_id',   'in', $object_ids]
                            ]);
            if($actions_ids > 0) {
                // add attributes to the data set
                $res = $om->read('resiway\ActionLog', $actions_ids, ['action_id.name', 'object_id']);
                if($res > 0) {
                    foreach($res as $actionLog_id => $actionLog) {
                        $history[$actionLog['object_id']][$actionLog['action_id.name']] = true;                        
                    }
                }
            }
        }
        return $history;
    }    

    
    
    /**
    * Updates badges status for user and object author.
    * Note: once a badge has been awarded it will never be withdrawn.
    * In case some badge is not yet defined in resiway_userbadge table, this method takes care of updating resiway_userbadge table
    *
    * @param    mixed    $action        either action name (string) or action identifier (integer)
    * @param    string   $object_class  class of the targeted object (ex. 'resiexchange\Question')
    * @param    integer  $object_id     identifier of the object on which action is performed
    *
    * @return   boolean  returns true on succes, false if something went wrong
    */     
    public static function updateBadges($action, $object_class, $object_id) {         
// how to make sure that user actually just performed this action ?
// in session; list of performed actions (actionlogs_ids
        $result = [];
        
        $om = &ObjectManager::getInstance();

        // retrieve user object
        $user_id = self::userId();
        if($user_id <= 0) throw new Exception("user_unidentified", QN_ERROR_NOT_ALLOWED);

        // retrieve action object identifier
        $action_id = intval($action);
        if($action_id == 0) {
            // if we received a string, try to resolve action name
            $action_id = self::actionId($action);
            if($action_id <= 0) throw new Exception("action_unknown", QN_ERROR_INVALID_PARAM);
        }
        
        // retrieve action data
        $res = $om->read('resiway\Action', $action_id, ['badges_ids']);
        if($res < 0 || !isset($res[$action_id])) throw new Exception("action_unknown", QN_ERROR_INVALID_PARAM);
        $action_data = $res[$action_id];

        // retrieve author (might be 0 for some objects)
        $res = $om->read($object_class, $object_id, ['creator']);
        if(!is_array($res) || !isset($res[$object_id])) throw new Exception("user_unidentified", QN_ERROR_INVALID_PARAM);
        $author_id = $res[$object_id]['creator'];

        
        // get user badges impacted by current action
        $user_badges_ids = $om->search('resiway\UserBadge', [['badge_id', 'in', $action_data['badges_ids']], ['user_id', '=', $user_id]]);
        // read status and related badge identifier
        $res = $om->read('resiway\UserBadge', $user_badges_ids, ['awarded', 'badge_id']);
        if($res < 0) return;
        // remove badges already awarded from result list
        foreach($user_badges_ids as $key => $user_badge_id) {
            if($res[$user_badge_id]['awarded']) unset($user_badges_ids[$key]);
        }
        // check list of badges identifiers returned by read method against impacted badges identifiers
        $missing_user_badges = array_diff($action_data['badges_ids'], array_map(function($a){return $a['badge_id'];}, $res));
        // create missing badges, if any         
        foreach($missing_user_badges as $badge_id) {
            $user_badges_ids[] = $om->create('resiway\UserBadge', ['user_id' => $user_id, 'badge_id' => $badge_id]);
        }
        
        // author badges impacted by current action   
        $author_badges_ids = [];
        // process author badges, in case there is an author
        if($author_id) {
            // get user badges impacted by current action
            $author_badges_ids = $om->search('resiway\UserBadge', [['badge_id', 'in', $action_data['badges_ids']], ['user_id', '=', $author_id]]);
            // read status and related badge identifier
            $res = $om->read('resiway\UserBadge', $author_badges_ids, ['awarded', 'badge_id']);
            if($res < 0) return;
            // remove badges already awarded from result list
            foreach($author_badges_ids as $key => $user_badge_id) {
                if($res[$user_badge_id]['awarded']) unset($author_badges_ids[$key]);
            }
            // check list of badges identifiers returned by read method against impacted badges identifiers
            $missing_author_badges = array_diff($action_data['badges_ids'], array_map(function($a){return $a['badge_id'];}, $res));
            // create missing badges, if any         
            foreach($missing_author_badges as $badge_id) {
                $author_badges_ids[] = $om->create('resiway\UserBadge', ['user_id' => $author_id, 'badge_id' => $badge_id]);
            }
        }        


        // get badges impacted by current action
        // $users_badges_ids = $om->search('resiway\UserBadge', [['badge_id', 'in', $action_data['badges_ids']], ['user_id', 'in', array($user_id, $author_id)]]);
        $users_badges_ids = array_merge($user_badges_ids, $author_badges_ids);
        if($users_badges_ids < 0 || !count($users_badges_ids)) return;

        // force re-computing values of impacted badges
        $om->write('resiway\UserBadge', $users_badges_ids, array('status' => null));
        // read new status and other data
        $res = $om->read('resiway\UserBadge', $users_badges_ids, ['user_id', 'badge_id', 'status', 'badge_id.type', 'badge_id.name']);
        if($res < 0) return; 
        
        // check for newly awarded badges
        foreach($res as $user_badge_id => $user_badge) {
            // remove non-awarded badges from list
            if($user_badge['status'] < 1) unset($res[$user_badge_id]);
        }
        
        // mark all newly awarded badges at once
        $om->write('resiway\UserBadge', array_keys($res), array('awarded' => '1'));
        // keep track of users badges-counts update
        $bagdes_increments = [];
        // do some treatment to inform user that a new badge has been awarded to him
        foreach($res as $user_badge_id => $user_badge) {
            
            $uid = $user_badge['user_id'];
            $bid = $user_badge['badge_id'];
            $user_data = self::loadUserPrivate($uid);
            
            if(!isset($bagdes_increments[$uid])) $bagdes_increments[$uid] = array(1 => 0, 2 => 0, 3 => 0);
            ++$bagdes_increments[$uid][ $user_badge['badge_id.type'] ];
            
            $data = [
                'user' => $user_data,
                'object' => ['id' => $bid, 'name' => $user_badge['badge_id.name'] ]
            ];

            $notification = self::getUserNotification('notification_badge_awarded', $user_data['language'], $data);
            self::userNotify($uid, 'badge_awarded', $notification);

        }
        // update user badges-counts, if required
        foreach($bagdes_increments as $uid => $bagdes_increment) {
            $res = $om->read('resiway\User', $uid, ['count_badges_1','count_badges_2','count_badges_3']);
            if($res > 0 && isset($res[$uid])) {
                $om->write('resiway\User', $uid, [ 
                                                    'count_badges_1'=> $res[$uid]['count_badges_1']+$bagdes_increment[1],
                                                    'count_badges_2'=> $res[$uid]['count_badges_2']+$bagdes_increment[2],
                                                    'count_badges_3'=> $res[$uid]['count_badges_3']+$bagdes_increment[3] 
                                                  ]);
            }
        }
        return $result;
    }


    
    /**
    *
    * This method throws an error if some rule is broken or if something goes wrong
    * 
    * @param mixed      $action_name
    * @param string     $object_class
    * @param integer    $object_id
    * @param string     $toggle             indicates the kind of action (repeated actions or toggle between on and off / performed - not performed)
    * @param array      $fields             fields that are going to be impacted by the action (and therefore need to be loaded)
    * @param array      $limitations        array of functions that will raise an error in case some constrainst is violated
    * @param function   $do                 operations to perform by default
    * @param function   $undo               operations to perform in case of toggle (undo action) or concurrent action has already be performed (undo concurrent action)
    */ 
    public static function performAction(
                                        $action_name, 
                                        $object_class, 
                                        $object_id,
                                        $object_fields = [],                                        
                                        $toggle = false,
                                        $do = null,
                                        $undo = null,        
                                        $limitations = []) {
        
        $result = true;
       
        $om = &ObjectManager::getInstance();
                    
        // 0) retrieve parameters 
        
        // retrieve object data (making sure defaults fields are loaded)
        if($object_id > 0) {
            $res = $om->read($object_class, $object_id, array_merge(['id', 'creator', 'created', 'modified', 'modifier'], $object_fields));
            if($res < 0 || !isset($res[$object_id])) throw new Exception("object_unknown", QN_ERROR_INVALID_PARAM);   
        }
        
        // retrieve current user identifier
        $user_id = self::userId();
        if($user_id <= 0) throw new Exception("user_unidentified", QN_ERROR_NOT_ALLOWED);

        // retrieve action object
        $action_id = self::actionId($action_name);
        if($action_id <= 0) throw new Exception("action_unknown", QN_ERROR_INVALID_PARAM);
        
        // 1) check rights
        
        if(!self::isActionAllowed($user_id, $action_id, $object_class, $object_id)) {
            throw new Exception("user_reputation_insufficient", QN_ERROR_NOT_ALLOWED);  
        }
        
        // 2) check action limitations
        
        foreach($limitations as $limitation) {
            if(is_callable($limitation)) {
                call_user_func($limitation, $om, $user_id, $action_id, $object_class, $object_id);
            }
        }

        // 3) & 4) log/unlog action and update reputation
        
        // determine which operation has to be performed ($do or $undo)        
        if($toggle && self::isActionRegistered($user_id, $action_id, $object_class, $object_id)) {
            self::unregisterAction($user_id, $action_name, $object_class, $object_id);        
            $result = $undo($om, $user_id, $object_class, $object_id);                    
        }
        else {
            self::registerAction($user_id, $action_name, $object_class, $object_id);        
            $result = $do($om, $user_id, $object_class, $object_id);            
        }
  
        return $result;
    }
    
}