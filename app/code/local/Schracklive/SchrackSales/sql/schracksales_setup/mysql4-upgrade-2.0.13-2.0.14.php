<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

ALTER TABLE `{$installer->getTable('sales_flat_quote_address')}` ADD `schrack_is_custom_addr` INT(1) DEFAULT 0 NOT NULL;
");

$installer->endSetup();



?>
