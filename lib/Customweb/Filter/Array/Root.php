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
//require_once 'Customweb/Filter/Array/Root.php';
//require_once 'Customweb/Filter/Array/ComplexNode.php';



class Customweb_Filter_Array_Root {
	private $rootElement;
	
	public function __construct() {
		$this->rootElement = new Customweb_Filter_Array_ComplexNode('root');
	}
	
	public static function _() {
		return new Customweb_Filter_Array_Root();
	}
	
	public function filter() {
		$output = $this->rootElement->filter();
		if (isset($output[$this->getName()])) {
			return $output[$this->getName()];
		}
		else {
			return array();
		}
	}
	
	public function add(Customweb_Filter_Array_INode $node) {
		$this->rootElement->add($node);
		return $this;
	}
	
	public function getChildren() {
		$this->rootElement->getChildren();
	}

	public function getName() {
		return $this->rootElement->getName();
	}
}