<?php

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$tableName = $installer->getTable('cataloginventory/stock_item');
$connection->addColumn($tableName,'stock_location', 'VARCHAR(20)');

$installer->endSetup();

?>
