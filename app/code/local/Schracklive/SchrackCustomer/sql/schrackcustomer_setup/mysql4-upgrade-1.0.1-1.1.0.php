<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->addAttribute('customer', 'schrack_telephone', array('type'=>'varchar','required'=>false,'label'=>'Telephone'));
$installer->addAttribute('customer', 'schrack_fax', array('type'=>'varchar','required'=>false,'label'=>'Fax'));
$installer->addAttribute('customer', 'schrack_mobile_phone', array('type'=>'varchar','required'=>false,'label'=>'Mobile Phone'));

$installer->addAttribute('customer_address', 'schrack_type', array('type'=>'static','required'=>false,'label'=>'Address Type','source'=>'customer/address_attribute_source_type'));
$installer->addAttribute('customer_address', 'schrack_wws_address_number', array('type'=>'static','required'=>false,'label'=>'Address Number'));
$installer->addAttribute('customer_address', 'schrack_additional_phone', array('type'=>'varchar','required'=>false,'label'=>'Telephone 2'));

$installer->removeAttribute('customer_address', 'schrack_mobile_phone');

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('customer_entity')} ADD `created_by` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('customer_entity')} ADD `updated_by` varchar(255) NOT NULL default '';

ALTER TABLE {$this->getTable('customer_address_entity')} ADD `created_by` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('customer_address_entity')} ADD `updated_by` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('customer_address_entity')} ADD `schrack_type` tinyint(4);
ALTER TABLE {$this->getTable('customer_address_entity')} ADD `schrack_wws_address_number` int(11);

    ");

$installer->endSetup();

// create Magento customer addresses for the system contacts of the accounts from the obsolete account addresses
$account = Mage::getModel('account/account');
$address = Mage::getModel('customer/address');
$connection = $installer->getConnection();
$select = $connection->select()
	->from('account_address')
	->where("wws_customer_id!='' AND wws_address_number>0");
foreach($connection->fetchAll($select) as $row) {
	$account->loadByWwsCustomerId($row['wws_customer_id']);
	if ($account->getId()) {
		$customer = $account->getSystemContact();
		if ($customer->getId()) {
			$address->setData(array());	// clear attributes

			$address->setCustomerId($customer->getId());
			$address->setSchrackWwsAddressNumber($row['wws_address_number']);
			$address->setSchrackType($row['type']);
			$address->setName1($row['name1']);
			$address->setName2($row['name2']);
			$address->setName3($row['name3']);
			$address->setTelephone($row['phone1']);
			$address->setFax($row['fax']);
			$address->setSchrackAdditionalPhone($row['phone2']);
			$address->setStreet($row['street']);
			$address->setCity($row['city']);
			$address->setPostcode($row['postcode']);
			$address->setCountryId($row['country_id']);
			$address->save();
		}
	}
}
