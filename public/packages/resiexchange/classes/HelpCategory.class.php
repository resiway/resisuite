<?php
namespace resiexchange;

class HelpCategory extends \easyobject\orm\Object {

    public static function getColumns() {
        return array(

            /* name of the category */
            'title'				    => array(
                                        'type'          => 'string', 
                                        'multilang'     => true,
                                        'onchange'      => 'resiexchange\HelpCategory::onchangeTitle'                                        
                                       ),

            /* title URL-formatted (for links) */
            'title_url'             => array(
                                        'type'          => 'function',
                                        'result_type'   => 'string',
                                        'store'         => true, 
                                        'function'      => 'resiexchange\HelpCategory::getTitleURL'
                                       ),
                                       
            /* text describing the category */
            'description'			=> array('type' => 'text', 'multilang' => true),

            /* value for ordering categories between them */
            'order'	        		=> array('type' => 'integer'),
            
            /* identifiers of the topics in this category */                                        
            'topics_ids'            => array(
                                        'type'		    => 'one2many', 
                                        'foreign_object'=> 'resiexchange\HelpTopic', 
                                        'foreign_field'	=> 'category_id'
                                        )              
            
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
        $om->write('resiexchange\HelpCategory', $oids, ['title_url' => null], $lang);        
    }    



    public static function getTitleURL($om, $oids, $lang) {
        $result = [];
        $res = $om->read('resiexchange\HelpCategory', $oids, ['title']);
        foreach($res as $oid => $odata) {
            // note: final format will be: #/help/category/{id}/{title}
            $result[$oid] = self::slugify($odata['title'], 200);
        }
        return $result;        
    }    
}