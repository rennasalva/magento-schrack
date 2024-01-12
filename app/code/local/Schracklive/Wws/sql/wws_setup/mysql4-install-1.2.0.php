<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_check_wws_order` tinyint DEFAULT '0' NOT NULL;

    ");

$installer->endSetup();
