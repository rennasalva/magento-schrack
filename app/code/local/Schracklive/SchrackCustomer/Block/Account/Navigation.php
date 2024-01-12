<?php

class Schracklive_SchrackCustomer_Block_Account_Navigation extends Mage_Customer_Block_Account_Navigation {

	public function removeLink($name) {

		unset($this->_links[$name]);
	}
	
}
