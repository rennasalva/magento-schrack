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
//require_once 'Customweb/Filter/Input/Integer.php';
//require_once 'Customweb/I18n/Translation.php';

/**
 *
 * @author Thomas Hunziker
 * Filter for integers. If set to FILTER_MODE_CLEAN the value will be rounded (cast), otherwise will fail when encountering non-numeric characters
 * (e.g. "1.00"). Will validate against a maximum and minimum value.
 */
class Customweb_Filter_Input_Integer extends Customweb_Filter_Input_AbstractNumeric {

	
	public static function _($input, $min = null, $max = null, $mode = null) {
		return new Customweb_Filter_Input_Integer($input, $min, $max, $mode);
	}
	
	public function filter(){
		$input = (int) $this->getInput();
		
		if ($this->isFailFilterModeActive()) {
			if (!preg_match('/[0-9]+/', $this->getInput())) {
				throw new Customweb_Filter_Exception(Customweb_I18n_Translation::__("Contains non numeric chars."));
			}
		}
		
		$input = $this->filterMax($input);
		$input = $this->filterMin($input);
		
		return $input;
	}
}