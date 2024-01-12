<?php

/**
 *  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */
//require_once 'Customweb/Filter/Exception.php';
//require_once 'Customweb/Filter/Input/AbstractNumeric.php';
//require_once 'Customweb/Filter/Input/Float.php';
//require_once 'Customweb/I18n/Translation.php';



/**
 *
 * @author Thomas Hunziker
 * Filter for decimal numbers.
 * If set to FILTER_MODE_CLEAN the value will be rounded to the closest valid value, otherwise will fail when encountering non-numeric characters in
 * valid (e.g. "#1.50").
 * Will validate against a maximum and minimum value.
 */
class Customweb_Filter_Input_Float extends Customweb_Filter_Input_AbstractNumeric {
	private $decimalPlaces = 0;
	private $separator;

	public function __construct($input, $decimalPlaces, $min = null, $max = null, $separator = '.', $mode = null){
		parent::__construct($input, $min, $max, $mode);
		$this->separator = (string) $separator;
		
		$decimalPlaces = (int) $decimalPlaces;
		if ($decimalPlaces < 0) {
			throw new Exception("The decimal places must be equal or greater than 0.");
		}
		$this->decimalPlaces = $decimalPlaces;
	}
	
	public static function _($input, $decimalPlaces, $min = null, $max = null, $separator = '.', $mode = null) {
		return new Customweb_Filter_Input_Float($input, $decimalPlaces, $min, $max, $separator, $mode);
	}

	public function filter(){
		$input = (float) $this->getInput();
		
		if ($this->isFailFilterModeActive()) {
			if (!preg_match('/[0-9]+\.?[0-9]*/', (string) $this->getInput())) {
				throw new Customweb_Filter_Exception(Customweb_I18n_Translation::__("Contains non numeric chars."));
			}
		}
		
		$input = round($input, $this->getDecimalPlaces());
		
		$input = $this->filterMax($input);
		$input = $this->filterMin($input);
		
		return number_format($input, $this->getDecimalPlaces(), $this->getSeparator(), '');
	}

	protected function getSeparator(){
		return $this->separator;
	}

	protected function getDecimalPlaces(){
		return $this->decimalPlaces;
	}
}