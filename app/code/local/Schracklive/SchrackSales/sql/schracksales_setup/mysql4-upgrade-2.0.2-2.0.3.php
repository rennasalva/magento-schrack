<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$tableNameShipment           = $installer->getTable('sales/shipment');

$installer->run("
    ALTER TABLE {$tableNameShipment}   ADD schrack_wws_parcels   VARCHAR(128) DEFAULT NULL after schrack_wws_reference;
");
$installer->endSetup();

/* make sure we have the same setup as all other order fields */

?>
