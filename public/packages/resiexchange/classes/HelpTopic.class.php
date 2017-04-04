<?php
namespace resiexchange;

class HelpTopic extends \easyobject\orm\Object {

    public static function getColumns() {
        return array(

            /* subject of the topic */
            'title'				    => array(
                                        'type'          => 'string', 
                                        'multilang'     => true,
                                        'onchange'      => 'resiexchange\HelpTopic::onchangeTitle'                                        
                                       ),

            /* title URL-formatted (for links) */
            'title_url'             => array(
                                        'type'          => 'function',
                                        'result_type'   => 'string',
                                        'store'         => true, 
                                        'function'      => 'resiexchange\HelpTopic::getTitleURL'
                                       ),
                                       
            /* text covering the topic */
            'content'			    => array('type' => 'html', 'multilang' => true),
            
            /* identifier of the category to which the topic belongs to */
            'category_id'           => array('type' => 'many2one', 'foreign_object'=> 'resiexchange\HelpCategory')
                     
        );
    }
    
    public static function slugify($value) {
        // remove accentuated chars
        $value = htmlentities($value, ENT_QUOTES, 'UTF-8');
        $value = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', $value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        // remove all non-space-alphanum-dash chars
        $value = preg_replace('/[^\s-a-z0-9]/i', '', $value);
        // replace spaces with dashes
        $value = preg_replace('/[\s-]+/', '-', $value);           
        // trim the end of the string
        $value = trim($value, '.-_');
        return strtolower($value);
    }
        
    public static function onchangeTitle($om, $oids, $lang) {
        // force re-compute title_url
        $om->write('resiexchange\HelpTopic', $oids, ['title_url' => null], $lang);        
    }    

    public static function getTitleURL($om, $oids, $lang) {
        $result = [];
        $res = $om->read('resiexchange\HelpTopic', $oids, ['title']);
        foreach($res as $oid => $odata) {
            // note: final format will be: #/help/topic/{id}/{title}
            $result[$oid] = self::slugify($odata['title'], 200);
        }
        return $result;        
    }        

   
}