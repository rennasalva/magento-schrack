<?php

class Schracklive_SchrackCheckout_Block_Onepage_Billing extends Mage_Checkout_Block_Onepage_Billing
{

	protected function _construct() {
		parent::_construct();

		if ($this->isCustomerLoggedIn() && ($this->getCustomer()->isContact() || $this->getCustomer()->isProspect())) {
			$this->getCheckout()->setStepData('billing', 'allow', false);
			$this->getCheckout()->setStepData('billing', 'complete', true);
		}
	}

	public function getAddressesHtmlSelect($type) {
		if ($type == 'billing' &&
				$this->isCustomerLoggedIn() &&
				($this->getCustomer()->isContact() || $this->getCustomer()->isProspect())) {
			$address = $this->getCustomer()->getPrimaryBillingAddress();
			$options = array(
				array(
					'value' => $address->getId(),
					'label' => $address->format('oneline')
				)
			);

			$select = $this->getLayout()->createBlock('core/html_select')
					->setName($type . '_address_id')
					->setId($type . '-address-select')
					->setClass('address-select')
					->setValue($address->getId())
					->setOptions($options);

			return $select->getHtml();
		} else {
			return $this->getAddressesHtmlSelectMulti($type);
		}
	}

	public function getAddressesHtmlSelectMulti($type)
	{
		if ($this->isCustomerLoggedIn()) {
			$options = array();
			foreach ($this->getCustomer()->getAddresses() as $address) {
				if ( $address->getSchrackWwsAddressNumber() > 0 ) {
					$options[] = array(
						'value' => $address->getId (),
						'label' => $address->format ('oneline')
					);
				}
			}

			$addressId = $this->getAddress()->getCustomerAddressId();
			if (empty($addressId)) {
				if ($type=='billing') {
					$address = $this->getCustomer()->getPrimaryBillingAddress();
				} else {
					$address = $this->getCustomer()->getPrimaryShippingAddress();
				}
				if ($address && $address->getSchrackWwsAddressNumber() > 0 ) {
					$addressId = $address->getId();
				}
			}

			$select = $this->getLayout()->createBlock('core/html_select')
				->setName($type.'_address_id')
				->setId($type.'-address-select')
				->setClass('address-select')
				->setExtraParams('onchange="'.$type.'.newAddress(!this.value)"')
				->setValue($addressId)
				->setOptions($options);

			$select->addOption('', Mage::helper('checkout')->__('New Address'));

			return $select->getHtml();
		}
		return '';
	}


}
