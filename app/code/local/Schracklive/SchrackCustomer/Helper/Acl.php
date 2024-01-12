<?php

class Schracklive_SchrackCustomer_Helper_Acl {

	/**
	 * @param Zend_Acl_Resource_Interface|string $resource
	 * @param string                             $privilege
	 * @param Mage_Customer_Model_Customer|null  $customer
	 * @return mixed
	 */
	public function isAllowed($resource, $privilege, Mage_Customer_Model_Customer $customer=NULL) {
		if (is_null($customer)) {
			$customer = Mage::getSingleton('customer/session')->getCustomer();
		}

		if ($customer->getSchrackAclRole() == 'list_price_customer') {
            if ($resource == 'price') {
                return false;
            }
		}

		return Mage::getSingleton('schrack/service_acl')->isAllowed($customer->getSchrackAclRole(), $resource, $privilege);
	}

}

