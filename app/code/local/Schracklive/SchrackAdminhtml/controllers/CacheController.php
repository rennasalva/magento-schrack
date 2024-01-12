<?php

require_once('Mage/Adminhtml/controllers/CacheController.php');

class Schracklive_SchrackAdminhtml_CacheController extends Mage_Adminhtml_CacheController {

	/**
	 * Check if cache management is allowed
	 *
	 * @return bool
	 */
	protected function _isAllowed() {
		// Magento 1.4 Bugfix
		return Mage::getSingleton('admin/session')->isAllowed('system/cache');
	}

}

?>