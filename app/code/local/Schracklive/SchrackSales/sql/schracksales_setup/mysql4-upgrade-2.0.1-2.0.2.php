<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$tableNameOrder              = $installer->getTable('sales/order');
$tableNameShipment           = $installer->getTable('sales/shipment');
$tableNameInvoice            = $installer->getTable('sales/invoice');
$tableNameCreditMemo         = $installer->getTable('sales/creditmemo');

$installer->run("
    ALTER TABLE {$tableNameOrder}      ADD schrack_wws_creation_date   DATETIME DEFAULT NULL after schrack_wws_order_number;
    ALTER TABLE {$tableNameOrder}      ADD schrack_wws_offer_date      DATETIME DEFAULT NULL after schrack_wws_offer_number;

    ALTER TABLE {$tableNameShipment}   ADD schrack_wws_document_date   DATETIME DEFAULT NULL after schrack_wws_shipment_number;
    
    ALTER TABLE {$tableNameInvoice}    ADD schrack_wws_document_date   DATETIME DEFAULT NULL after schrack_wws_invoice_number;
    
    ALTER TABLE {$tableNameCreditMemo} ADD schrack_wws_document_date   DATETIME DEFAULT NULL after schrack_wws_creditmemo_number;
");
$installer->endSetup();

/* make sure we have the same setup as all other order fields */
$installer->addAttribute('order', 'schrack_wws_creation_date', array('type'=>'static','required'=>false,'label'=>'Creation Date'));
$installer->addAttribute('order', 'schrack_wws_offer_date', array('type'=>'static','required'=>false,'label'=>'Offer Date'));

?>
