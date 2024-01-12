<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE `customer_entity` ADD COLUMN `schrack_changepw_token` varchar(128) NULL;
");

$installer->addAttribute('customer', 'schrack_changepw_token',	array('type' => 'static', 'required' => false, 'label' => 'Change PW Token'));

$installer->endSetup();