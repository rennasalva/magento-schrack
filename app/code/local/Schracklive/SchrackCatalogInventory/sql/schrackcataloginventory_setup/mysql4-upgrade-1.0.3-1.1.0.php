<?php

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();
$stockTableName = $installer->getTable('cataloginventory/stock');
$itemTableName = $installer->getTable('cataloginventory/stock_item');

$installer->run("
    DROP INDEX stock_number ON $stockTableName;
    ALTER TABLE $stockTableName ADD COLUMN stock_location VARCHAR(20) AFTER stock_number;
    UPDATE $stockTableName SET stock_location = 'SLV' WHERE stock_number = 999;
    INSERT INTO $stockTableName (stock_name, stock_number, stock_location, is_pickup, is_delivery, delivery_hours, xml_address)
        SELECT stock_name, stock_number, 'MH', is_pickup, is_delivery, delivery_hours, null FROM $stockTableName WHERE stock_location = 'SLV';
    INSERT INTO $stockTableName (stock_name, stock_number, stock_location, is_pickup, is_delivery, delivery_hours, xml_address)
        SELECT stock_name, stock_number, 'ONE', is_pickup, is_delivery, delivery_hours, null FROM $stockTableName WHERE stock_location = 'SLV';
    UPDATE $itemTableName SET stock_id = (SELECT stock_id FROM $stockTableName WHERE stock_location = 'MH') WHERE stock_location = 'MH';
    UPDATE $itemTableName SET stock_id = (SELECT stock_id FROM $stockTableName WHERE stock_location = 'ONE') WHERE stock_location = 'ONE';
");

$h = $connection->fetchOne("SELECT delivery_hours+216 FROM $stockTableName WHERE stock_number = 80");
$installer->run("
    UPDATE $stockTableName SET delivery_hours = $h WHERE stock_location = 'ONE';
");

$installer->endSetup();

?>
