<?php
/**
  * You are allowed to use this API in your web application.
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
//require_once 'Customweb/Filter/Array/Leaf.php';
//require_once 'Customweb/Filter/Array/INode.php';


class Customweb_Filter_Array_Leaf implements Customweb_Filter_Array_INode{
	
	/**
	 * @var string
	 */
	private $name;
	
	/**
	 * @var Customweb_Filter_IInput
	 */
	private $input;
	
	/**
	 * @var string
	 */
	private $label;
	
	public function __construct($name, Customweb_Filter_IInput $input, $label = null) {
		$this->input = $input;
		$this->name = $name;
		$this->label = $label;
	}
	
	/**
	 * Static constructor.
	 * 
	 * @param string $name
	 * @param Customweb_Filter_IInput $input
	 * @return Customweb_Filter_Array_Leaf
	 */
	public static function _($name, Customweb_Filter_IInput $input, $label = null) {
		return new Customweb_Filter_Array_Leaf($name, $input, $label);
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function filter() {
		try {
			$value = $this->getInput()->filter();
		}
		catch(Customweb_Filter_Exception $e) {
			$name = $this->getName();
			if ($this->getLabel() !== null) {
				$name = $this->getLabel();
			}
			throw new Customweb_Filter_Exception(Customweb_I18n_Translation::__("Invalid input provided for @name (!reason).", array('@name' => $name, '!reason' => $e->getLocalizableMessage())));
		}
			
		return array(
			$this->getName() => $value,
		);
	}
	
	/**
	 * @return Customweb_Filter_IInput
	 */
	protected function getInput() {
		return $this->input;
	}
	
	/**
	 * Returns the label (human readable name) of the leaf.
	 * 
	 * @return string
	 */
	protected function getLabel() {
		return $this->label;
	}

}