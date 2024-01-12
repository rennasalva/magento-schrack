<?php

class Schracklive_SchrackCore_Model_Email_Template_Filter extends Mage_Core_Model_Email_Template_Filter {

	protected $_currentAdditionalFontClose;

	/**
	 * Retrieve font directive
	 *
	 * @param array $construction
	 * @return string
	 */
	public function fontDirective($construction) {
		if (trim($construction[2]) == '/') {
			return $this->_currentAdditionalFontClose.'</font>';
		} else {
			$params = $this->_getIncludeParameters($construction[2]);

			// @todo configure
			if ((isset($params['type']))&&($params['type'] == 'head')) {
				$this->_currentAdditionalFontClose = '</b>';
				return '<font size="2" color="#005096" face="Arial" style="font-family:Arial,sans-serif;color:#005096;font-size:14px;font-weight:bold;"><b>';
			} else {
				$this->_currentAdditionalFontClose = '';
				return '<font size="1" color="#343434" face="Arial" style="font-family:Arial,sans-serif;color:#343434;font-size:12px">';
			}
		}
	}

}
