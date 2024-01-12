<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_custom_order_number` varchar(20);

ALTER TABLE `{$installer->getTable('sales_order')}` ADD `schrack_custom_order_number` varchar(20);

");

$installer->endSetup();

/* make sure we have the same setup as all other order fields */
$installer->addAttribute('order', 'schrack_custom_order_number', array('type'=>'static','required'=>false,'label'=>'Custom Order Number'));

?>