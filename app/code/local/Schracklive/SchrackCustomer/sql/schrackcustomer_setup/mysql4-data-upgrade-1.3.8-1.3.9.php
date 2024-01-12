<?php

$installer = $this;

$customers = Mage::getResourceModel('customer/customer_collection')
		->addAttributeToSelect('group_id')
		->addAttributeToSelect('schrack_acl_role_id');
foreach ($customers as $customer) {
	if (!$customer->getSchrackAclRoleId()) {
		if ($customer->getGroupId() == Mage::getStoreConfig('schrack/shop/system_group')) {
			$customer->setSchrackAclRoleId(Mage::helper('schrack/acl')->getSystemContactRoleId());
		} elseif ($customer->getGroupId() == Mage::getStoreConfig('schrack/shop/employee_group')) {
			$customer->setSchrackAclRoleId(Mage::helper('schrack/acl')->getEmployeeRoleId());			
		}
		Mage::getResourceSingleton('customer/customer')->saveAttribute($customer, 'schrack_acl_role_id');
	}
}
