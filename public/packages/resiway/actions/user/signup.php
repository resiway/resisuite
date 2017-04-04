<?php
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNlib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;
use easyobject\orm\PersistentDataManager as PersistentDataManager;
use html\HtmlTemplate as HtmlTemplate;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce(	
	array(	
    'description'	=>	"Attempt to register a new user.",
    'params' 		=>	array(
                        'login'	    =>  array(
                                        'description'   => 'email address of the user.',
                                        'type'          => 'string', 
                                        'required'      => true
                                        ),
                        'firstname'	=>  array(
                                        'description'   => 'user\'s firstname',
                                        'type'          => 'string', 
                                        'required'      => true
                                        ),
                        'lang'	    =>  array(
                                        'description'   => 'user\'s prefered language',
                                        'type'          => 'string', 
                                        'default'       => 'fr'
                                        )                                        
                        )
	)
);


list($result, $error_message_ids) = [true, []];

list($action_name, $login, $firstname, $language) = [ 
    'resiway_user_signup',
    strtolower(trim($params['login'])),
    $params['firstname'],
    $params['lang']
];

$messages_folder = '../spool';

try {
    $om = &ObjectManager::getInstance();
    $pdm = &PersistentDataManager::getInstance();

    // check login format validity
    $userClass = &$om->getStatic('resiway\User');
    $constraints = $userClass::getConstraints();    
    if(!$constraints['login']['function']($login)) throw new Exception("user_invalid_login", QN_ERROR_INVALID_PARAM);   
    if(!$constraints['firstname']['function']($firstname)) throw new Exception("user_invalid_firstname", QN_ERROR_INVALID_PARAM);       
    
    // make sure no account has already been created for this email address
    $ids = $om->search('resiway\User', ['login', '=', $login]);
    if($ids < 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN); 
    
    if(count($ids)) throw new Exception("user_already_registered", QN_ERROR_NOT_ALLOWED);

    // generate a random password (32 bytes hexadecimal values)
    // force first 8 bytes to NULL (this serves as marking to know which passwords haven't been changed)
    $password = '00000000';
    for($i = 0; $i < 24; ++$i) {
        $password .= sprintf("%x", rand(0, 15)) ;
    }
    // generate avatar URL using identicon with a random hash
    $avatar_url = 'http://www.gravatar.com/avatar/'.md5($firstname.rand()).'?d=identicon&s=@size';
    
    $user_id = $om->create('resiway\User', ['login'=>$login, 'password'=>$password, 'firstname' => $firstname, 'language' => $language, 'avatar_url' => $avatar_url]);
    if($user_id <= 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);

    // initialize list of awarded badges
    $badges_ids = $om->search('resiway\Badge');
    $badges = $om->read('resiway\Badge', $badges_ids, ['id']);    
    foreach($badges as $badge_id => $badge_data) {
        $om->create('resiway\UserBadge', ['user_id' => $user_id, 'badge_id' => $badge_id]);
    }

    // update global counter
    ResiAPI::repositoryInc('resiway.count_users');     
    
    // update session data
    $pdm->set('user_id', $user_id);
    
    // retrieve newly created user
    $user_data = ResiAPI::loadUserPrivate($user_id);
    // we need the password to generate confirmation code in the email
    $user_data['password'] = $password;

    
    // subject of the email should be defined in the template, as a <var> tag holding a 'title' attribute
    $subject = '';
    // read template according to user prefered language
    $file = "packages/resiway/i18n/{$user_data['language']}/mail_user_confirm.html";
    if(!($html = @file_get_contents($file, FILE_TEXT))) throw new Exception("action_failed", QN_ERROR_UNKNOWN);
    $template = new HtmlTemplate($html, [
                                'subject'		=>	function ($params, $attributes) {
                                                        global $subject;
                                                        $subject = $attributes['title'];
                                                        return '';
                                                    },
                                'username'		=>	function ($params, $attributes) {
                                                        return $params['firstname'];
                                                    },
                                'confirm_url'	=>	function ($params, $attributes) {
                                                        $code = ResiAPI::credentialsEncode($params['login'],$params['password']);
                                                        $url = QNlib::get_url(true, false)."user/confirm/{$code}";
                                                        return "<a href=\"$url\">{$attributes['title']}</a>";
                                                    }
                                ], 
                                $user_data);
    // parse template as html
    $body = $template->getHtml();
    
    /**
    * message files format is: 11 digits (user unique identifier) with 3 digits extension in case of multiple files
    */
    $temp = sprintf("%011d", $user_id);
    $filename = $temp;
    $i = 0;
    while(file_exists("$messages_folder/{$filename}")) {
        $filename = sprintf("%s.%03d", $temp, ++$i);
    }
    // data consists of parsed template and subject
    $json = json_encode(array("subject" => $subject, "body" => $body), JSON_PRETTY_PRINT);
    file_put_contents("$messages_folder/$filename", $json);
    
    // log user registration
    ResiAPI::registerAction($user_id, $action_name, 'resiway\User', $user_id);
}
catch(Exception $e) {
    $error_message_ids = array($e->getMessage());
    $result = $e->getCode();
}

// send json result
header('Content-type: application/json; charset=UTF-8');
echo json_encode([
                    'result'            => $result, 
                    'error_message_ids' => $error_message_ids
                 ], JSON_PRETTY_PRINT);