<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE sales_flat_shipment ADD INDEX IDX_SALES_FLAT_SHIPMENT_SCHRACK_WWS_SHIPMENT_NUMBER (schrack_wws_shipment_number) USING BTREE;
ALTER TABLE sales_flat_invoice ADD INDEX IDX_SALES_FLAT_INVOICE_SCHRACK_WWS_INVOICE_NUMBER (schrack_wws_invoice_number) USING BTREE;
ALTER TABLE sales_flat_creditmemo ADD INDEX IDX_SALES_FLAT_CREDITMEMO_SCHRACK_WWS_CREDITMEMO_NUMBER (schrack_wws_creditmemo_number) USING BTREE;
");

$installer->endSetup();


?>
