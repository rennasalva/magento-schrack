<?php

/*
 * we need a primary key that is not the customer_id reference to customer_entity, because
 * otherwise magento won't recognize a new record
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(
    "ALTER TABLE `{$installer->getTable('sales_flat_quote')}` ADD `schrack_wws_inpost_id` varchar(64);"
);

$installer->addAttribute('order', 'schrack_wws_inpost_id', array('type'=>'static','required'=>false,'label'=>'Inpost Id'));

$installer->endSetup();
