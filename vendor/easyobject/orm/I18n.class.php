<?php
namespace easyobject\orm;
use config as config;

class I18n {
	private $translations;

	private static $iso_639 = array('aa'=>'Afar', 'ab'=>'Abkhazian', 'ae'=>'Avestan', 'af'=>'Afrikaans', 'ak'=>'Akan', 'am'=>'Amharic', 'an'=>'Aragonese', 'ar'=>'Arabic', 'as'=>'Assamese', 'av'=>'Avaric', 'ay'=>'Aymara', 'az'=>'Azerbaijani', 'ba'=>'Bashkir', 'be'=>'Belarusian', 'bg'=>'Bulgarian', 'bh'=>'Bihari', 'bi'=>'Bislama', 'bm'=>'Bambara', 'bn'=>'Bengali', 'bo'=>'Tibetan', 'br'=>'Breton', 'bs'=>'Bosnian', 'ca'=>'Catalan', 'ce'=>'Chechen', 'ch'=>'Chamorro', 'co'=>'Corsian', 'cr'=>'Cree', 'cs'=>'Czech', 'cu'=>'Church Slavic', 'cv'=>'Chuvash', 'cy'=>'Welsh', 'da'=>'Danish', 'de'=>'German', 'dv'=>'Divehi', 'dz'=>'Dzongkha', 'ee'=>'Ewe', 'el'=>'Greek', 'en'=>'English', 'en_AU'=>'English/Australia', 'en_CA'=>'English/Canada', 'en_GB'=>'English/Great Britain', 'en_US'=>'English/United States', 'eo'=>'Esperanto', 'es'=>'Spanish', 'es_AR'=>'Spanish/Argentina', 'es_CO'=>'Spanish/Colombia', 'es_ES'=>'Spanish/Spain', 'es_MX'=>'Spanish/Mexico', 'et'=>'Estonian', 'eu'=>'Basque', 'fa'=>'Persian', 'ff'=>'Fulah', 'fi'=>'Finnish', 'fj'=>'Fijian', 'fo'=>'Faroese', 'fr'=>'French', 'fr_BE'=>'French/Belgium', 'fr_CA'=>'French/Canada', 'fr_FR'=>'French/France', 'fy'=>'Western Frisian', 'ga'=>'Irish', 'gd'=>'Scottish Gaelic', 'gl'=>'Galician', 'gn'=>'Guarani', 'gu'=>'Gujarati', 'gv'=>'Manx', 'ha'=>'Hausa', 'he'=>'Hebrew', 'hi'=>'Hindi', 'ho'=>'Hiri Motu', 'hr'=>'Croatian', 'ht'=>'Haitian', 'hu'=>'Hungarian', 'hy'=>'Armenian', 'hz'=>'Herero', 'ia'=>'Interlingua', 'id'=>'Indonesian', 'ie'=>'Interlingue', 'ig'=>'Igbo', 'ii'=>'Sichuan Yi', 'ik'=>'Inupiaq', 'io'=>'Ido', 'is'=>'Icelandic', 'it'=>'Italian', 'iu'=>'Inuktitut', 'ja'=>'Japanese', 'jv'=>'Javanese', 'ka'=>'Georgian', 'kg'=>'Kongo', 'ki'=>'Kikuyu', 'kj'=>'Kwanyama', 'kk'=>'Kazakh', 'kl'=>'Kalaallisut', 'km'=>'Khmer', 'kn'=>'Kannada', 'ko'=>'Korean', 'kr'=>'Kanuri', 'ks'=>'Kashmiri', 'ku'=>'Kurdish', 'kv'=>'Komi', 'kw'=>'Cornish', 'ky'=>'Kirghiz', 'la'=>'Latin', 'lb'=>'Luxembourgish', 'lg'=>'Ganda', 'li'=>'Limburgish', 'ln'=>'Lingala', 'lo'=>'Lao', 'lt'=>'Lithuanian', 'lu'=>'Luba-Katanga', 'lv'=>'Latvian', 'mg'=>'Malagasy', 'mh'=>'Marshallese', 'mi'=>'Maori', 'mk'=>'Macedonian', 'ml'=>'Malayalam', 'mn'=>'Mongolian', 'mo'=>'Moldavian', 'mr'=>'Marathi', 'ms'=>'Malay', 'mt'=>'Maltese', 'my'=>'Burmese', 'na'=>'Nauru', 'nb'=>'Norwegian Bokmål', 'nd'=>'North Ndebele', 'ne'=>'Nepali', 'ng'=>'Ndonga', 'nl'=>'Dutch', 'nn'=>'Norwegian Nynorsk', 'no'=>'Norwegian', 'nr'=>'South Ndebele', 'nv'=>'Navajo', 'ny'=>'Chichewa', 'oc'=>'Occitan', 'oj'=>'Ojibwa', 'om'=>'Oromo', 'or'=>'Oriya', 'os'=>'Ossetian', 'pa'=>'Panjabi', 'pi'=>'Pali', 'pl'=>'Polish', 'ps'=>'Pashto', 'pt'=>'Portuguese', 'pt_BR'=>'Portuguese/Brazil', 'pt_PT'=>'Portuguese/Portugal', 'qu'=>'Quechua', 'rm'=>'Raeto-Romance', 'rn'=>'Kirundi', 'ro'=>'Romanian', 'ru'=>'Russian', 'rw'=>'Kinyarwanda', 'sa'=>'Sanskrit', 'sc'=>'Sardinian', 'sd'=>'Sindhi', 'se'=>'Northern Sami', 'sg'=>'Sango', 'si'=>'Sinhalese', 'sk'=>'Slovak', 'sl'=>'Slovene', 'sm'=>'Samoan', 'sn'=>'Shona', 'so'=>'Somali', 'sq'=>'Albanian', 'sr'=>'Serbian', 'ss'=>'Swati', 'st'=>'Sotho', 'su'=>'Sundanese', 'sv'=>'Swedish', 'sw'=>'Swahili', 'ta'=>'Tamil', 'te'=>'Telugu', 'tg'=>'Tajik', 'th'=>'Thai', 'ti'=>'Tigrinya', 'tk'=>'Turkmen', 'tl'=>'Tagalog', 'tn'=>'Tswana', 'to'=>'Tonga', 'tr'=>'Turkish', 'ts'=>'Tsonga', 'tt'=>'Tatar', 'tw'=>'Twi', 'ty'=>'Tahitian', 'ug'=>'Uighur', 'uk'=>'Ukrainian', 'ur'=>'Urdu', 'uz'=>'Uzbek', 've'=>'Venda', 'vi'=>'Vietnamese', 'vo'=>'Volapük', 'wa'=>'Walloon', 'wo'=>'Wolof', 'xh'=>'Xhosa', 'yi'=>'Yiddish', 'yo'=>'Yoruba', 'za'=>'Zhuang', 'zh'=>'Chinese', 'zh_CN'=>'Chinese/China', 'zh_HK'=>'Chinese/Hong-Kong', 'zh_SG'=>'Chinese/Singapur', 'zh_TW'=>'Chinese/Taiwan','zu'=>'Zulu');

	private function __construct() {
		$this->translations = array();
	}

	public static function &getInstance()	{
		if (!isset($GLOBALS['I18n_instance'])) $GLOBALS['I18n_instance'] = new I18n();
		return $GLOBALS['I18n_instance'];
	}

	public static function is_code($code) {
		return isset(self::$iso_639[$code]);
	}

	public static function loadLocale($locale) {
		$file = "packages/core/i18n/{$locale}/locale.inc.php";
		if(!I18n::is_code($locale) || !is_file($file)) return false;
		include($file);
		return true;
	}

	public static function getLocale($locale) {
		$result = false;
		// save config current state
		$temp = config\get_config();
		// reset config
		config\set_config(array());		
		if(self::loadLocale($locale)) {
			// retrieve parameters set in the locale.inc.php script
			$result = config\get_config();
		}
		else {
			$result = UNKNOWN_OBJECT;
		}
		// restore config to its original state
		config\set_config($temp);
		return $result;
	}
	
	public function loadTranslationFile($code, $package, $class) {
		$file = "packages/{$package}/i18n/{$code}/{$class}.json";
		if($json_data = @file_get_contents($file, FILE_TEXT)) {
			$this->translations[$package][$code][$class] = json_decode($json_data, true);
		}
		else $this->translations[$package][$code][$class] = UNKNOWN_OBJECT;
		return $this->translations[$package][$code][$class];
	}

	
    /**
    * Gets the translation value of some part of a class.
    * This method allows to manage translations server-side.
    * However, keep in mind that it is recommanded to do translations on the client side.
    *
    * 	Path syntax: object_class => (class name), object_part => ('model' | 'view'), object_field => (field name), field_attr => ('label' | 'help' | 'sequence')
    *
    * @param string $code iso639 language identifier
    * @param array $path
    */
	public function getClassTranslationValue($code, $path=array()) {
		$res = false;
        // check request validity
		if(!I18n::is_code($code) || !in_array($path['object_part'], array('model', 'view'))) return false;
		$package = ObjectManager::getObjectPackageName($path['object_class']);
        $class = ObjectManager::getObjectName($path['object_class']);
		if(!isset($this->translations[$package][$code])) $this->loadTranslationFile($code, $package, $class);
		// check if the the term to be translated is present in the json file
		if(isset($this->translations[$package][$code][$class][$path['object_part']][$path['object_field']][$path['field_attr']]))
			$res = $this->translations[$package][$code][$class][$path['object_part']][$path['object_field']][$path['field_attr']];
		return $res;
	}
}