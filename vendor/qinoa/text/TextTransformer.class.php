<?php

namespace qinoa\text;


class TextTransformer {

    /**
    * Transforms given string to a standard ASCII string containing lowercase words separated by single spaces
    * (no accent, punctuation signs, quotes, plus nor dash)
    * This method expects a UTF-8 string
    */
    public static function normalize($value) {
        // note: remember to maintain current file charset to UTF-8 !
        $ascii = array(
            'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
            'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
            'Ï'=>'I', 'Ñ'=>'N', 'Ń'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 
            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 
            'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 
            'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ń'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o',
            'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f',
            'ă'=>'a', 'î'=>'i', 'â'=>'a', 'ș'=>'s', 'ț'=>'t', 'Ă'=>'A', 'Î'=>'I', 'Â'=>'A', 'Ș'=>'S', 'Ț'=>'T'
        );        
        $value = str_replace(array_keys($ascii), array_values($ascii), $value);
        // remove all non-quote-space-alphanum-dash chars
        $value = preg_replace('/[^\'\s-a-z0-9]/i', '', $value);
        // replace spaces, dashes and quotes with spaces
        $value = preg_replace('/[\s-\']+/', ' ', $value);           
        // trim the end of the string
        $value = trim($value, '.-_');

        return strtolower($value); 
    }


    /*
    * tries to convert a word to its most common form
    */
    public static function axiomize($word, $locale='fr') {
        static $locales = [
        'fr' => [
            'eaux'  => 'eau',
            'aux'   => 'al',
            'eux'   => 'eu',
            'oux'   => 'ou',
            's'     => '',
            'onne'  => 'on',
            'euse'  => 'eur',
            'rice'  => 'eur',
            'ere'   => 'er'
            ]
        ];
        $items = $locales[$locale];
        $word_len = strlen($word);
        foreach($items as $key => $val) {
            $key_len = strlen($key);
            if($word_len > $key_len) {
                if(substr($word, -$key_len) == $key) {
                    $word = substr($word, 0, -$key_len);
                    $word = $word.$val;
                    $word_len = strlen($word);
                }
            }
        }
        return $word;
    }    
    
    /**
    * Transform a string into a slug (URL-compatible words separated by dashes)
    * This method expects a UTF-8 string
    */
    public static function slugify($value) {    
        return str_replace(' ', '-', self::normalize($value));
    }

    
    /**
    * Generate a 64-bits integer hash from given string
    * returned value is intended to be stored in a UNISGNED BIGINT DBMS column (8 bytes/20 digits)
    */
    public static function hash($value) {
        return gmp_strval(gmp_init(substr(md5($value), 0, 16), 16), 10);
    }
}