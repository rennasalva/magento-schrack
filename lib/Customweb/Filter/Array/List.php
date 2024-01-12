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
//require_once 'Customweb/Filter/Array/List.php';
//require_once 'Customweb/Filter/Array/INode.php';



class Customweb_Filter_Array_List implements Customweb_Filter_Array_INode{
	
	private $children = array();
	
	private $name;
	
	public function __construct($name) {
		$this->name = $name;
	}
	
	public static function _($name) {
		return new Customweb_Filter_Array_List($name);
	}
	
	public function add(Customweb_Filter_Array_INode $node) {
		$this->children[] = $node;
		return $this;
	}
	
	public function getChildren() {
		return $this->children;
	}
	
	public function filter() {
		$output = array();
		foreach ($this->getChildren() as $child) {
			$output[] = $this->filterItem($child);
		}
		return array(
			$this->getName() => $output
		);
	}
	
	protected function filterItem(Customweb_Filter_Array_INode $node) {
		return $node->filter();
	}

	public function getName() {
		return $this->name;
	}

}