<?php
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');

use config\QNlib as QNLib;
use easyobject\orm\ObjectManager as ObjectManager;
use html\HtmlTemplate as HtmlTemplate;


/*

$params = [
    'code' => 'dGVzdGVyQGV4YW1wbGUuY29tOzAwMDAwMDAwYzU1OWMyNTA0N2JiNjk2OGMzYTE3NzQz',
    'increment' => '+5'
    ];
$template = '
<var id="subject" title="réputation mise à jour"></var>
<p>
Bonjour <var id="username"></var>,<br />
<br />
<var if="false">always shown</var>
<var if="score &gt; 0">score greater than 0</var>
<var if="increment &lt; 0">increment lower than 0</var><var if="increment &gt; 0">increment greater than 0</var>
<br />
Ceci est un message automatique envoyé depuis resiway.org suite à une demande de réinitialisation de mot de passe.<br />
Si vous n\'êtes pas à l\'origine de cette requête, ignorez simplement ce message.<br />
<br />
Si vous souhaitez continuer et définir un nouveau mot de passe maintenant, veuillez cliquer sur le lien ci-dessous.<br /> 
</p>

';

$subject = '';
$template = new HtmlTemplate($template, [
                                'subject'		=>	function ($params, $attributes) use(&$subject) {
                                                        $subject = $attributes['title'];
                                                        return '';
                                                    },
                                'score'		    =>	function ($params) {
                                                        return '+5';
                                                    },
                                'username'		=>	function ($params) {
                                                        return 'cedric';
                                                    },
                                'confirm_url'	=>	function ($params) {
                                                        return "<a href=\"http://resiway.gdn/resiexchange.fr#/user/confirm/{$params['code']}\">Valider mon adresse email</a>";
                                                        return "<a href=\"http://resiway.gdn/resiexchange.fr#/user/password/{$params['code']}\">Modifier mon mot de passe</a>";
                                                    }
                            ], 
                            $params);
                            
$body = $template->getHtml();
    

print($subject);
print($body);
*/
set_silent(false);

$om = &ObjectManager::getInstance();

$uid = 3;
$answers_ids = $om->search('resiexchange\Answer', ['creator', '=', $uid]);
$res = $om->read('resiexchange\Answer', $answers_ids, ['question_id']);
$questions_ids = array_map(function($a){return $a['question_id'];}, $res);


$res = $om->search('resiexchange\Question', [['id', 'in', $questions_ids], ['count_answers', '=', 1]]);

print_r($res);