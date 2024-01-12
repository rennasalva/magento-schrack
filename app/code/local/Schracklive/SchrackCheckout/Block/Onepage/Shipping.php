<?php

class Schracklive_SchrackCheckout_Block_Onepage_Shipping extends Mage_Checkout_Block_Onepage_Shipping {

	protected function _construct() {
		parent::_construct();

		if ($this->isCustomerLoggedIn() && ($this->getCustomer()->isContact() || $this->getCustomer()->isProspect())) {
			$this->getCheckout()->setStepData('shipping', 'allow', true);
		}
	}

	public function getAddressesHtmlSelect($type) {
		if ($type != 'shipping') {
			return '';
		}

		$addresses = $this->_getAddresses();
		$select = $this->getLayout()->createBlock('core/html_select');

		if (array_key_exists('current', $addresses) && array_key_exists('list', $addresses) ) {
			$select->setName($type.'_address_id')
					->setId($type.'-address-select')
					->setClass('address-select')
					->setValue($addresses['current'])
					->setOptions($addresses['list']);
		}

		if ($this->mayUseNewAddress()) {
			$select->addOption('', Mage::helper('checkout')->__('New Address'));
		}

		return $select->getHtml();
	}

	public function getAddressesHtmlRadio() {
		$addresses = $this->_getAddresses();

		$radio = '';
		foreach ($addresses['list'] as $address) {
			$id = 'shipping_address_id_'.preg_replace('[^a-z0-9]', '-', $address['value']);
			$value = htmlspecialchars($address['value']);
			$label = htmlspecialchars($address['label']);
			$checked = ($address['value'] == $addresses['current'] ? ' checked="checked"' : '');
			$radio .= '<input type="radio" name="shipping_address_id" id="'.$id.'" value="'.$value.'"'.$checked.'/>&nbsp;'
					.'<label for="'.$id.'">'.$label.'</label><br/>';
		}
		return $radio;
	}

	public function mayUseNewAddress() {
		return true;
		// return (($this->getCustomer()->isContact() || $this->getCustomer()->isProspect()) ? false : true);
	}

	protected function _getAddresses() {
		$addressId = $this->getAddress()->getId();
		if ( in_array($this->getCustomer()->getSchrackCustomerType(), array('light-prospect')) ) {
			if ($this->getAddress()&& in_array($this->getAddress()->getSchrackWwsAddressNumber(), array(0, 1))) {
				// This leadds to empty selectlist ==> no address found (-> customer must fill out shipping address):
				// Background: light prospect has addresses, but it's a dummy address:
				return array();
			}
		}

		if (empty($addressId)) {
			$address = $this->getCustomer()->getPrimaryShippingAddress();
			if ($address && $address->getSchrackWwsAddressNumber() > 0 && $address->getSchrackWwsAddressNumber() != Schracklive_SchrackCustomer_Model_Address::NO_ADDRESS_NUMBER ) {
				$addressId = $address->getId();
			}
		}

		$addressList = array();
		$addAddressWithoutNumberAllowed =     ! $this->getCustomer()->isAnyWwsContact()
                                           && count($this->getCustomer()->getAddresses()) < 2;
		foreach ($this->getCustomer()->getAddresses() as $address) {
			if (    (   $address->getSchrackWwsAddressNumber() > 0
	                 && $address->getSchrackWwsAddressNumber() != Schracklive_SchrackCustomer_Model_Address::NO_ADDRESS_NUMBER)
                 || $addAddressWithoutNumberAllowed
			   ) {
			    $format = $address->format('oneline');
			    $sort = is_array($address->getStreet()) && count($address->getStreet()) > 0 ? $address->getStreet()[0] : $format;
				$addressList[] = array(
					'value' => $address->getId(),
					'label' => $format,
                    'sort'  => $sort
				);
			}
		}
		usort($addressList,function ( $a, $b ) {
		   return strcmp($a['sort'],$b['sort']);
        });

		return array(
			'current' => $addressId,
			'list' => $addressList,
		);
	}

    protected function getRequestreceivers() {
        $model = Mage::getModel('schracksales/requestreceiver');
        $coll = $model->getCollection();
        return $coll;
    }


}
