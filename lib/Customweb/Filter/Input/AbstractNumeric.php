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
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Filter/Input/Abstract.php';

abstract class Customweb_Filter_Input_AbstractNumeric extends Customweb_Filter_Input_Abstract{
	
	private $min = null;
	
	private $max = null;
	
	public function __construct($input, $min = null, $max = null, $mode = null) {
		if ($mode === null) {
			$mode = self::FILTER_MODE_FAIL;
		}
		parent::__construct($input, $mode);
		$this->min = $min;
		$this->max = $max;
	}
	
	
	protected function filterMin($input) {
		if ($this->min !== null && $input < $this->min) {
			if ($this->isCleanFilterModeActive()) {
				$input = $this->min;
			}
			else {
				throw new Customweb_Filter_Exception(Customweb_I18n_Translation::__("Value must be at least @min", array('@min' => $this->min)));
			}
		}
		
		return $input;
	}
	
	protected function filterMax($input) {
		if ($this->max !== null && $input > $this->max) {
			if ($this->isCleanFilterModeActive()) {
				$input = $this->max;
			}
			else {
				throw new Customweb_Filter_Exception(Customweb_I18n_Translation::__("Value must be smaller than @max", array('@max' => $this->max)));
			}
		}
		
		return $input;
	}
}