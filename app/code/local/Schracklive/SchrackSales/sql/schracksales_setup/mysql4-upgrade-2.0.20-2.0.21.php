<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE sales_flat_order_schrack_index ADD INDEX IDX_SALES_FLAT_ORDER_SCHRACK_INDEX_DOC_NO (wws_document_number) USING BTREE;
ALTER TABLE sales_flat_order_schrack_index ADD INDEX IDX_SALES_FLAT_ORDER_SCHRACK_INDEX_FOLLOWUP_NO (wws_followup_order_number) USING BTREE;
");

$installer->endSetup();


?>
