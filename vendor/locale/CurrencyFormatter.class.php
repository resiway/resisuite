<?php

/*
This class is a basic implementation for currency formatting following ICU formatting. 

*/

class CurrencyFormatter {

/**
	testing unit :	
	echo "<pre>";
	echo get_currency_output(0)."\n";
	echo get_currency_output(1001)."\n";
	echo get_currency_output(100010.016)."\n";
	echo get_currency_output(100)."\n";	
	echo get_currency_output(11.1)."\n";
	echo get_currency_output(1.1)."\n";
	echo get_currency_output(-1.1)."\n";
*/

	private $format;
	private $params;
	
	public function __construct($format='#.##0,00€', $format_encoding='UTF-8') {
		$this->setFormat($format);			
	}
	
	public function setFormat($format) {
		$this->params = null;	
		$this->format = $format;			
	}
	
	private function init_formatting() {
		if(!isset($this->params)) {
			$this->params = array();
			mb_internal_encoding("UTF-8");		
			$decimal_count = 0;
			for($i = 0, $j = mb_strlen($this->format);$i < $j; ++$i) {
	//		for($i = 0, $j = strlen($this->format);$i < $j; ++$i) {		
				$char = mb_substr($this->format, $i, 1);
				// $char = substr($this->format, $i, 1);	
				if(!in_array($char, array('#','0',',','.')) && !isset($this->params['CURRENCY_SYMBOL'])) {
					$this->params['CURRENCY_SYMBOL'] = $char;
					if($i == 0) $this->params['CURRENCY_SYMBOL_POSITION'] = 'left';
					else $this->params['CURRENCY_SYMBOL_POSITION'] = 'right';
					continue;
				}
				if($char == '#' && !isset($this->params['CURRENCY_THOUSAND_SEPARATOR'])) {
					++$i;
					$this->params['CURRENCY_THOUSAND_SEPARATOR'] = mb_substr($this->format, $i, 1);
					continue;
				}
				if($char == '0') {
					if(!isset($this->params['CURRENCY_DECIMAL_SEPARATOR'])) {
						if($i+1 < $j) {
							++$i;
							$this->params['CURRENCY_DECIMAL_SEPARATOR'] = mb_substr($this->format, $i, 1);
						}
					}
					else $decimal_count++;
				}
			}

			$this->params['CURRENCY_DECIMAL_PRECISION'] = $decimal_count;
			if(!isset($this->params['CURRENCY_THOUSAND_SEPARATOR'])) $this->params['CURRENCY_THOUSAND_SEPARATOR'] = '';
			if(!isset($this->params['CURRENCY_DECIMAL_SEPARATOR'])) $this->params['CURRENCY_DECIMAL_SEPARATOR'] = '';
		}
	}

	public function convertString($value) {
		if(is_string($value)) {
			if(!isset($this->params)) $this->init_formatting();		
			$value = preg_replace('/[^0-9|,|.|+|-]/', '', $value);
			$value = str_replace($this->params['CURRENCY_DECIMAL_SEPARATOR'], '.', $value);			
			$value = str_replace($this->params['CURRENCY_THOUSAND_SEPARATOR'], '', $value);
			if(strpos($value, '.') === false && stripos($value, 'e') === false) $value = intval($value);
			else $value = floatval($value);
		}
		return $value;
	}

	public function getCurrencyOutput($value) {
		if(!isset($this->params)) $this->init_formatting();
		if(is_string($value)) $value = $this->convertString($value);
		$result = '';
		// get sign
		$sign = ($value < 0)?-1:1;		
		// remove sign from original value
		$value = ($sign < 0)?-$value:$value;		
		// get integer part 
		$integer = intval($value);
		if($integer == 0) $result = '0';
		else {
			for($i = $integer, $j = 0; $i > 0; ) {
				$part = $i % 1000;
				if($j) $result = $this->params['CURRENCY_THOUSAND_SEPARATOR'].$result;		
				if($i > 1000) $part = sprintf("%03d", $part);
				$result = $part.$result;
				++$j;
				$i = intval($i/1000); 
			}
		}
		// get decimal part
		if($this->params['CURRENCY_DECIMAL_PRECISION'] > 0) {
			$precision = pow(10, $this->params['CURRENCY_DECIMAL_PRECISION']);
			$decimal = round(($value-$integer)*$precision);
			$result .= $this->params['CURRENCY_DECIMAL_SEPARATOR'].sprintf('%0'.$this->params['CURRENCY_DECIMAL_PRECISION'].'d', $decimal);
		}
		// sign
		$result = ($sign < 0)?'-'.$result:$result;
		// symbol
		$result = ($this->params['CURRENCY_SYMBOL_POSITION']  == 'left')?$this->params['CURRENCY_SYMBOL'].$result:$result.$this->params['CURRENCY_SYMBOL'];
		return $result;	
	}

}
