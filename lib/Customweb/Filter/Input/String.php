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
//require_once 'Customweb/Core/String.php';
//require_once 'Customweb/Core/Charset.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Filter/Input/String.php';
//require_once 'Customweb/Filter/Input/Abstract.php';



/**
 *
 * @author Thomas Hunziker
 * Can filter a string against a charset and length. If set to FILTER_MODE_CLEAN will replace invalid characters and cut to length.
 */
class Customweb_Filter_Input_String extends Customweb_Filter_Input_Abstract {
	private $charset;
	private $allowedLength;

	/**
	 * Constructor.
	 *
	 * In clean up mode the input is cut of from the beginning and the not allowed chars are removed.
	 *
	 * @param string $input
	 * @param int $allowedLength Allowed length.
	 * @param Customweb_Core_Charset|string $charset The target char set (per default it is UTF-8)
	 * @param string $mode Constant of FILTER_MODE_* (default FILTER_MODE_CLEAN)
	 */
	public function __construct($input, $allowedLength, $charset = null, $mode = null){
		parent::__construct($input, $mode);
		
		if ($charset === null) {
			$this->charset = Customweb_Core_Charset::forName("UTF-8");
		}
		elseif (is_string($charset)) {
			$this->charset = Customweb_Core_Charset::forName($charset);
		}
		else if ($charset instanceof Customweb_Core_Charset) {
			$this->charset = $charset;
		}
		else {
			throw new Exception("The provided char set is invalid. It must be either identified by the name or the char set itself.");
		}
		
		$this->allowedLength = $allowedLength;
	}
	
	public static function _($input, $allowedLength, $charset = null, $mode = null) {
		return new Customweb_Filter_Input_String($input, $allowedLength, $charset, $mode);
	}

	public function filter(){
		$input = (string) $this->getInput();
		$input = $this->filterCharset($input);
		$input = $this->filterLength($input);
		return $input;
	}

	protected function filterLength($input){
		if (strlen($input) > $this->getAllowedLength()) {
			if ($this->isFailFilterModeActive()) {
				throw new Customweb_Filter_Exception(
						Customweb_I18n_Translation::__("The length is limited to @length chars.", array(
							'@length' => $this->getAllowedLength() 
						)));
			}
			else {
				$input = Customweb_Core_String::_($input)->substring(0, $this->getAllowedLength())->toString();
			}
		}
		return $input;
	}

	protected function filterCharset($input){
		if ($this->isFailFilterModeActive()) {
			Customweb_Core_Charset::setConversionBehavior(Customweb_Core_Charset::CONVERSION_BEHAVIOR_EXCEPTION);
		}
		else {
			Customweb_Core_Charset::setConversionBehavior(Customweb_Core_Charset::CONVERSION_BEHAVIOR_REPLACE);
		}
		try {
			return Customweb_Core_Charset::convert($input, Customweb_Core_Charset::forName("UTF-8"), $this->getTargetCharset());
		}
		catch (Customweb_Core_Exception_UnexpectedCharException $e) {
			throw new Customweb_Filter_Exception(Customweb_I18n_Translation::__("Invalid char '@char'", array(
				'@char' => $e->getCharAsUtf8() 
			)));
		}
	}

	/**
	 *
	 * @return int
	 */
	protected function getAllowedLength(){
		return $this->allowedLength;
	}

	/**
	 *
	 * @return Customweb_Core_Charset
	 */
	protected function getTargetCharset(){
		return $this->charset;
	}
}
