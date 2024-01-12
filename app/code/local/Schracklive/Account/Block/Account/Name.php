<?php

class Schracklive_Account_Block_Account_Name extends Schracklive_Account_Block_Template {

	public function _construct() {
		parent::_construct();

		// default template location
		$this->setTemplate('account/account/name.phtml');
	}

	/**
	 * Render block HTML
	 *
	 * @return string
	 */
	protected function _toHtml() {
		if (!$this->getAccount()) {
			return '';
		}
		return parent::_toHtml();
	}

	public function renderLine($value) {
		if ($value) {
			return htmlspecialchars($value)."<br/>\n";
		}
		return '';
	}

}
