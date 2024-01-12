<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->startSetup();
$installer->run("

ALTER TABLE `{$installer->getTable('customer_entity')}` ADD `schrack_account_id` int;
ALTER TABLE `{$installer->getTable('customer_entity')}` ADD `schrack_wws_customer_id` varchar(6);
ALTER TABLE `{$installer->getTable('customer_entity')}` ADD `schrack_wws_contact_number` int;
ALTER TABLE `{$installer->getTable('customer_entity')}` ADD `schrack_user_principal_name` varchar(255);

ALTER TABLE `{$installer->getTable('customer_entity')}` ADD INDEX `account` (`schrack_account_id`,`schrack_wws_contact_number`);

");

$installer->endSetup();

$installer->addAttribute('customer', 'schrack_account_id',
		array(
	'type' => 'static',
	'required' => false,
	'label' => 'Account')
);
