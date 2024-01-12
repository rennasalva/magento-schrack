<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE `customer_entity` ADD COLUMN `schrack_s4y_id` varchar(36) NULL AFTER `schrack_wws_contact_number`;
");

$installer->addAttribute('customer', 'schrack_s4y_id',	array('type' => 'static', 'required' => false, 'label' => 'Schrack4you ID'));

$installer->endSetup();