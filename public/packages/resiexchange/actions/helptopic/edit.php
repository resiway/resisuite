<?php
// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');

use config\QNLib as QNLib;
use html\HTMLPurifier as HTMLPurifier;
use easyobject\orm\DataAdapter as DataAdapter;

// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = QNLib::announce([
    'description'	=>	"Edit a help topic or submit a new one",
    'params' 		=>	[
        'topic_id'	=> array(
                            'description'   => 'Identifier of the topic being edited (a null identifier means creation of a new topic).',
                            'type'          => 'integer', 
                            'default'       => 0
                            ),    
        'title'	        => array(
                            'description'   => 'Title of the submitted topic.',
                            'type'          => 'string', 
                            'required'      => true
                            ),
        'content'	    => array(
                            'description'   => 'Content of the submitted topic.',
                            'type'          => 'string', 
                            'required'      => true
                            ),
        'category_id'   => array(
                            'description'   => 'Parent category identifier.',
                            'type'          => 'integer',
                            'required'      => true
                            ),                            
    ]
]);


list($result, $error_message_ids, $notifications) = [true, [], []];

list($action_name, $object_class, $object_id, $title, $content, $tags_ids) = [ 
    'resiexchange_helptopic_edit',
    'resiexchange\HelpTopic',
    $params['topic_id'],
    $params['title'],
    $params['content'],
    $params['category_id']
];

// override ORM method for cleaning HTML (for field 'content')
DataAdapter::setMethod('ui', 'orm', 'html', function($value) {
    $purifier = new HTMLPurifier(ResiAPI::getHTMLPurifierConfig());    
    return $purifier->purify($value);
});


// handle new topic submission 
// which has a distinct reputation requirement
if($object_id == 0) $action_name = 'resiexchange_helptopic_post';


try {
// try to perform action
    $result = ResiAPI::performAction(
        $action_name,                                             // $action_name
        $object_class,                                            // $object_class
        $object_id,                                               // $object_id
        [],                                                       // $object_fields
        false,                                                    // $toggle
        function ($om, $user_id, $object_class, $object_id)       // $do
        use ($params) {    
        
            if($object_id == 0) {
                // create a new topic + write given value
                $object_id = $om->create('resiexchange\HelpTopic', [ 
                                'creator'           => $user_id,     
                                'title'             => $params['title'],
                                'content'           => $params['content'],
                                'category_id'       => $params['category_id']                            
                              ]);

                if($object_id <= 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);
            }
            else {
                $om->write($object_class, $object_id, [
                                'modifier'          => $user_id, 
                                'title'             => $params['title'],
                                'content'           => $params['content'],
                                'category_id'       => $params['category_id']
                           ]);
            }
            
            // read created topic as returned value
            $res = $om->read($object_class, $object_id, ['id', 'creator', 'created', 'title', 'title_url', 'content', 'category_id']);
            if($res < 0) throw new Exception("action_failed", QN_ERROR_UNKNOWN);
          
            return $res[$object_id];
        },
        null,                                                      // $undo
        [                                                          // $limitations
        ]
    );
    
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