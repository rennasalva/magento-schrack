<?php

class Schracklive_SchrackCustomer_Block_Account_Administrationlink extends Mage_Core_Block_Abstract {

	public function addAdminLink() {
		if (Mage::getSingleton('customer/session')->getCustomer()->isAllowed('accessRight', 'edit')) {
			if ($parentBlock = $this->getParentBlock()) {
				$parentBlock->addLink($this->__('Manage Accounts'), 'customer/accountadministration/', $this->__('Manage Accounts'),
						true, array(), 50, null);
			}
		}
	}

}
