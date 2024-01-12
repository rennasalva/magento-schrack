<?php

$installer = $this;

$customers = Mage::getResourceModel('customer/customer_collection')
		->addAttributeToSelect('group_id')
		->addAttributeToSelect('schrack_acl_role_id');
foreach ($customers as $customer) {
	if (!$customer->getSchrackAclRoleId()) {
		if ($customer->getGroupId() == Mage::getStoreConfig('schrack/shop/contact_group')) {
			$customer->setSchrackAclRoleId(Mage::helper('schrack/acl')->getAdminRoleId());
		} elseif ($customer->getGroupId() == Mage::getStoreConfig('schrack/shop/inactive_contact_group')) {
			$customer->setSchrackAclRoleId(Mage::helper('schrack/acl')->getDefaultRoleId());			
		}
		Mage::getResourceSingleton('customer/customer')->saveAttribute($customer, 'schrack_acl_role_id');
	}
}
