<?php

class FSManipulator {

	public static function getDirListing($dir_name) {
		if(!is_dir($dir_name)) return null;
		if(($dir = opendir($dir_name)) === false) return null;
		$d_pos = 0;
		$f_pos = 0;
		$list_directoies = array();
		$list_files = array();
		while ($node = readdir($dir)) {
			if ($node != '.' && $node != '..') {
				if (is_dir($dir_name.'/'.$node)) {
					$list_directoies[$d_pos] = $node;
					++$d_pos;
				}
				else{
					$list_files[$f_pos] = $node;
					++$f_pos;
				}
			}
		}
		closedir($dir);
		sort($list_directoies);
		sort($list_files);
		return array_merge($list_directoies, $list_files);
	}

	public static function getFileStat($file_name) {
		$fp = fopen($file_name, "r");
		$fstat = fstat($fp);
		fclose($fp);
		return $fstat;
	}

	public static function getLastChange($file_name) {
		$fstat = self::getFileStat($file_name);
		return $fstat['mtime'];
	}

	public static function getFileContent($file_name) {
		$handle = fopen($file_name, 'rb');
		$content = fread($handle, filesize($file_name));
		fclose($handle);
		return $content;
	}

	public static function getTempName($file_name) {
		for($ext = 0;;$ext++) {
			$temp_name = $file_name.sprintf("%03d", $ext);
			if(!is_file($temp_name)) break;
		}
		return $temp_name;
	}

	public static function getSanitizedName($file_name) {
        // remove accentuated chars
        $file_name = htmlentities($file_name, ENT_QUOTES, 'UTF-8');
        $file_name = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', $value);
        $file_name = html_entity_decode($file_name, ENT_QUOTES, 'UTF-8');
        // remove all non space-alphanum-dot-underscore-dash chars
        $file_name = preg_replace('/[^\s\.-_a-z0-9]/i', '', $file_name);
        // replace spaces with underscores
        $file_name = preg_replace('/[\s-]+/', '_', $file_name);           
        // trim the end of the string
        $file_name = trim($file_name, ' .-_');
        // make string lowercase and return
        return strtolower($file_name);
	}

}