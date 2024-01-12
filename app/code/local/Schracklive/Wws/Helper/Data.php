<?php

class Schracklive_Wws_Helper_Data extends Mage_Core_Helper_Abstract {

	/**
	 * Gets a configured instance of a SOAP client
	 *
	 * @throws Schracklive_Wws_Exception|Mage_Core_Exception
	 * @return Schracklive_Schrack_Model_Soap_Client
	 */
	function createSoapClient() {
		$options = array(
			'schrack_system' => 'wws',
		);
		if (Mage::getStoreConfig('schrack/wws/socket_timeout')) {
			$options['schrack_socket_timeout'] = (int)Mage::getStoreConfig('schrack/wws/socket_timeout');
		}
		if (Mage::getStoreConfig('schrackdev/wws/log')) {
			$options['schrack_log_transfer'] = true;
		}
		if (!Mage::getStoreConfig('schrack/wws/wsdl')) {
			throw Mage::exception('Schracklive_Wws', 'No WSDL for WWS connection configured.');
		}
		return Mage::helper('schrack/soap')->createClient(Mage::getStoreConfig('schrack/wws/wsdl'), $options);
	}

	function getWwsCustomerId(Mage_Customer_Model_Customer $customer) {
		if ($customer->isContact() 
				|| $customer->isSystemContact() 
				|| ($customer->isProspect() && $customer->getSchrackWwsCustomerId())) {
			$customerId = $customer->getSchrackWwsCustomerId();
		} else {
			$customerId = $this->getAnonymousWwsCustomerId();
		}

		// Checks for projectant role (may see only default price):
		$sessionCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
		$aclRoleId = Mage::getModel('customer/customer')->load($sessionCustomerId)->getSchrackAclRoleId();
		$isProjectant = Mage::helper('schrack/acl')->isProjectantRoleId($aclRoleId);
		if ($isProjectant) {
			$customerId = $sessionCustomerId;
		}

		return $customerId;
	}

	function getWwsCustomerIdForProductInfo(Mage_Customer_Model_Customer $customer) {
		if ($customer->isAllowed('price', 'view')) {
			return $this->getWwsCustomerId($customer);
		} else {
			return $this->getAnonymousWwsCustomerId();
		}
	}

	function getAnonymousWwsCustomerId() {
		return sprintf('TYP=%s', strtoupper(Mage::helper('schrack')->getCountryTld()));
	}

	function getWwsAuthentication() {
		return new Varien_Object(
				array(
					'sender_id' => Mage::getStoreConfig('schrack/wws/sender_id'),
					'user' => Mage::getStoreConfig('schrack/wws/user'),
					'password' => Mage::getStoreConfig('schrack/wws/password'),
				)
		);
	}

}
