<?php

/*
 * one wishlist per customer will be our default wishlist
 */

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$installer->run("
alter table sales_flat_order_schrack_index add index (document_date_time);
alter table sales_flat_order add key (schrack_wws_order_number);
alter table sales_flat_order_schrack_index_position add key (parent_id, sku, description);
");
$installer->endSetup();

/* make sure we have the same setup as all other order fields */

?>