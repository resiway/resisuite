<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use easyobject\orm\PersistentDataManager as PersistentDataManager;
use mail\Swift_SmtpTransport as Swift_SmtpTransport;
use mail\Swift_Mailer as Swift_Mailer;
use mail\Swift_Message as Swift_Message;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce([
    'description'	=>	"Request pending emails",
    'params' 		=>	[
    ]
]);


list($result, $error_message_ids) = [true, []];
$messages_folder = '../spool';

try {
    
    $user_id = ResiAPI::userId();

    if($user_id > 0) {
        $user_data = ResiAPI::loadUserPrivate($user_id);
        // retrieve user's messages
        $file = sprintf("%011d", $user_id);
        $files = glob("{$messages_folder}/{$file}*", GLOB_NOSORT);
        // try to send each matching file
        foreach($files as $filename) {
            try {
                if( !($json = @file_get_contents($filename, FILE_TEXT)) ) continue;
                $params = json_decode($json, true);

                if(!isset($params['subject']) || !isset($params['body'])) continue;
                
                $transport = Swift_SmtpTransport::newInstance(EMAIL_SMTP_HOST, EMAIL_SMTP_PORT, "ssl")
                            ->setUsername(EMAIL_SMTP_ACCOUNT_USERNAME)
                            ->setPassword(EMAIL_SMTP_ACCOUNT_PASSWORD);

                $message = Swift_Message::newInstance($params['subject'])
                            ->setFrom(array(EMAIL_SMTP_ACCOUNT_USERNAME => 'ResiWay'))
                            ->setTo(array($user_data['login']))
                            // some webmail require a text/plain part as default content
                            ->setBody($params['body'])
                            // in most cases, if a text/html part is found it will be displayed by default
                            ->addPart($params['body'], 'text/html');
                            
                $mailer = Swift_Mailer::newInstance($transport);

                $mailer->send($message);
                // remove file once processed                
                unlink($filename);
            }
            catch(Exception $e) {
                // todo : log errors (unreadable files)
            }
        }
    }
}
catch(Exception $e) {
    $result = $e->getCode();
    $error_message_ids = array($e->getMessage());
}

// send json result
header('Content-type: application/json; charset=UTF-8');
echo json_encode([
        'result'            => $result, 
        'error_message_ids' => $error_message_ids
    ], 
    JSON_PRETTY_PRINT);