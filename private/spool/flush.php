#!/usr/bin/env php
<?php
/**
 Force spool to send all pending emails
*/
use easyobject\orm\ObjectManager as ObjectManager;
use mail\Swift_SmtpTransport as Swift_SmtpTransport;
use mail\Swift_Mailer as Swift_Mailer;
use mail\Swift_Message as Swift_Message;

// run this script as if it were located in the public folder
chdir('../../public');
set_time_limit(0);

// this utility script uses qinoa library
// and requires file config/config.inc.php
require_once('../qn.lib.php');

$messages_folder = '../spool';

list($result, $error_message_ids) = [true, []];

set_silent(false);

try {
    $files = scandir($messages_folder);

    foreach($files as $file) {
        if(in_array($file, ['.', '..'])) continue;
        
        // extract user identifier
        $user_id = intval(explode('.', $file)[0]);
        
        // wrong file format
        if($user_id == 0) continue;
        
        // retrieve file full path
        $filename = $messages_folder.'/'.$file;
        
        $om = &ObjectManager::getInstance();        
        // get login from user_id 
        $res = $om->read('resiway\User', $user_id, ['login'] );

        if($res < 0 || !isset($res[$user_id])) throw new Exception(sprintf("user_unidentified (%d, %s)", $user_id, $file), QN_ERROR_NOT_ALLOWED);   
        $user_data = $res[$user_id];

        // read file content
        if( !($json = @file_get_contents($filename, FILE_TEXT)) ) continue;
        $params = json_decode($json, true);

        if(!isset($params['subject']) || !isset($params['body'])) {
            unlink($filename); 
            continue;
        }

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
}
catch(Exception $e) {
    $result = $e->getCode();
    $error_message_ids = array($e->getMessage());
}

// send json result
echo json_encode([
        'result'            => $result, 
        'error_message_ids' => $error_message_ids
    ], 
    JSON_PRETTY_PRINT);