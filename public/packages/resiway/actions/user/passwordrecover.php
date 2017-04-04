<?php
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');

use config\QNlib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;
use easyobject\orm\PersistentDataManager as PersistentDataManager;
use html\HtmlTemplate as HtmlTemplate;
use mail\Swift_SmtpTransport as Swift_SmtpTransport;
use mail\Swift_Mailer as Swift_Mailer;
use mail\Swift_Message as Swift_Message;

require_once('../resi.api.php');

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce(	
	array(	
    'description'	=>	"Send an email with instructions to recover password.",
    'params' 		=>	array(
                        'email'	=>  array(
                                    'description'   => 'email address associated with the account to recover.',
                                    'type'          => 'string', 
                                    'required'      => true
                                    )
                                     
                        )
	)
);

list($result, $error_message_ids) = [true, []];


try {
    $om = &ObjectManager::getInstance();
    // check if provided email address is registerd

    $login = $params['email'];
    
    // retrieve user id
    $ids = $om->search('resiway\User', ['login', '=', $login]);    
    if($ids < 0 || !count($ids)) throw new Exception("action_failed", QN_ERROR_UNKNOWN);
    $user_id = $ids[0];
    
    // retrieve md5 hash of current password
    $res = $om->read('resiway\User', $user_id, ['login', 'firstname', 'password', 'language']);
    if($res < 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);
    $user_data = $res[$user_id];

    // subject of the email should be defined in the template, as a <var> tag holding a 'title' attribute
    $subject = '';
    $to = $user_data['login'];
    // read template according to user prefered language    
    $file = "packages/resiway/i18n/{$user_data['language']}/mail_user_passwordreset.html";
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
                                                        $url = QNlib::get_url(true, false)."user/password/{$code}";
                                                        return "<a href=\"$url\">{$attributes['title']}</a>";
                                                    }
                                ], 
                                $user_data);
                            
    $body = $template->getHtml();

    $transport = Swift_SmtpTransport::newInstance(EMAIL_SMTP_HOST, EMAIL_SMTP_PORT, "ssl")
                ->setUsername(EMAIL_SMTP_ACCOUNT_USERNAME)
                ->setPassword(EMAIL_SMTP_ACCOUNT_PASSWORD);
                
    $message = Swift_Message::newInstance($subject)
                ->setFrom(array(EMAIL_SMTP_ACCOUNT_USERNAME => 'ResiWay'))
                ->setTo(array($to))
                ->setBody($body)
                ->setContentType("text/html");
                
    $mailer = Swift_Mailer::newInstance($transport);
    
    $result = $mailer->send($message);
   
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
