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
//require_once 'Customweb/IFilter.php';
//require_once 'Customweb/Filter/IInput.php';

abstract class Customweb_Filter_Input_Abstract implements Customweb_Filter_IInput {
	
	/**
	 * In case the filter detected that the input violates the filter rule, the filter method will
	 * throw an exception.
	 * 
	 * @var string
	 */
	const FILTER_MODE_FAIL = 'fail';
	
	/**
	 * In case the filter detected that the input violates the filter rule, the filter method will
	 * try to clean up the input.
	 *
	 * @var string
	 */
	const FILTER_MODE_CLEAN = 'clean';
	
	private $input;
	
	private $mode;

	public function __construct($input, $mode = null) {
		if ($mode == self::FILTER_MODE_FAIL) {
			$this->mode = self::FILTER_MODE_FAIL;
		}
		else {
			$this->mode = self::FILTER_MODE_CLEAN;
		}
		
		if ($input instanceof Customweb_IFilter) {
			$this->input = $input->filter();
		}
		else {
			$this->input = $input;
		}
	}
	
	protected function getInput() {
		return $this->input;
	}
	
	protected function getFilterMode() {
		return $this->mode;
	}
	
	protected function isFailFilterModeActive() {
		return $this->getFilterMode() == self::FILTER_MODE_FAIL;
	}
	
	protected function isCleanFilterModeActive() {
		return $this->getFilterMode() == self::FILTER_MODE_CLEAN;
	}
	
	
	
}
