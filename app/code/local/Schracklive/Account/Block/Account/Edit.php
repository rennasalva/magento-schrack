<?php

class Schracklive_Account_Block_Account_Edit extends Schracklive_Account_Block_Template {

	protected function _prepareLayout() {
		$this->getLayout()->getBlock('head')
				->setTitle($this->getTitle());

		return parent::_prepareLayout();
	}

	public function getTitle() {
		if ($title = $this->getData('title')) {
			return $title;
		}
		if (0 && $this->getAddress()->getId()) {
			$title = Mage::helper('account')->__('Edit Address');
		} else {
			$title = Mage::helper('account')->__('Edit Company');
		}
		return $title;
	}

	public function mayEdit() {
		// @todo add ACL check
		return $this->getCustomer()->isContact() || $this->getCustomer()->isProspect();
	}

}

