<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE sales_flat_order_schrack_index ADD INDEX IDX_SALES_FLAT_ORDER_SCHRACK_INDEX_ORDER_ID (order_id) USING BTREE;
ALTER TABLE sales_flat_order_schrack_index ADD INDEX IDX_SALES_FLAT_ORDER_SCHRACK_INDEX_SHIPMENT_ID (shipment_id) USING BTREE;
ALTER TABLE sales_flat_order_schrack_index ADD INDEX IDX_SALES_FLAT_ORDER_SCHRACK_INDEX_INVOICE_ID (invoice_id) USING BTREE;
ALTER TABLE sales_flat_order_schrack_index ADD INDEX IDX_SALES_FLAT_ORDER_SCHRACK_INDEX_CREDITMEMO_ID (credit_memo_id) USING BTREE;
");

$installer->endSetup();


?>
