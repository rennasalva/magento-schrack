<?php

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();
$stockTableName = $installer->getTable('cataloginventory/stock');
$itemTableName = $installer->getTable('cataloginventory/stock_item');

$installer->run("
    ALTER TABLE cataloginventory_stock ADD COLUMN locked_until TIMESTAMP null AFTER stock_number;
");

$installer->endSetup();

?>
