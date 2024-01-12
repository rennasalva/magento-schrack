<?php

/**
 * Customer address edit block
 *
 */
class Schracklive_SchrackCustomer_Block_Address_Edit extends Mage_Customer_Block_Address_Edit {

	protected function _prepareLayout() {
		parent::_prepareLayout();

		// Repeat init address object for contacts
		if (($id = $this->getRequest()->getParam('id'))) {
			$customer = $this->getCustomer();
			if ($customer->isContact() || $this->getCustomer()->isProspect()) {
				// re-load address data
				$this->_address->load($id);
				if ($this->_address->getCustomerId() != $customer->getSystemContact()->getId()) {
					$this->_address->setData(array());
				}

				// unset defaults of base class
				if (!$this->_address->getId()) {
					$this->_address->setPrefix('')
							->setFirstname('')
							->setMiddlename('')
							->setLastname('')
							->setSuffix('');
				}

				// re-apply post data
				if (($postedData = Mage::getSingleton('customer/session')->getAddressFormData(true))) {
					$this->_address->setData($postedData);
				}
			}
		}
	}

	public function canSetAsDefaultBilling() {
		if ($this->getCustomer()->isContact() || $this->getCustomer()->isProspect()) {
			return false;
		}
		return parent::canSetAsDefaultBilling();
	}

	public function mayChangeDefaultBilling() {
		if ($this->getCustomer()->isContact()) {
			return false;
		}
		return true;
	}

	public function getSchrackType() {
		if ($type = $this->getAddress()->getSchrackType()) {
			return $type;
		}
		return constant(Mage::getConfig()->getHelperClassName('schrackcustomer/address').'::DEFAULT_ADDRESS_TYPE');
	}

	public function getTypeHtmlSelect($value=null, $name='schrack_type', $id='schrack-type', $title='Address Type') {
		if (is_null($value)) {
			$value = $this->getSchrackType();
		}

		$options = Mage::getModel('schrackcustomer/entity_address_attribute_source_type')->getStandardOptions();

		$html = $this->getLayout()->createBlock('core/html_select')
						->setName($name)
						->setId($id)
						->setTitle(Mage::helper('schrackcustomer')->__($title))
						->setClass('validate-select form-control')
						->setValue($value)
						->setOptions($options)
						->getHtml();

		return $html;
	}

    public function getZipCodeRegex($countryId) {
        return Mage::getModel('geoip/country')->getZipCodeRegex(strtolower($countryId));
    }

	public function getTitle($actionName = 'new') {
		if ($title = $this->getData('title')) {
			return $title;
		}
		if ($actionName == 'edit') {
			$title = Mage::helper('customer')->__('Edit Address');
		}
		if ($actionName == 'new') {
			$title = Mage::helper('customer')->__('Add New Address');
		}
		return $title;
	}
}
