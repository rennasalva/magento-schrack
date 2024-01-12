<?php

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$tableName = $installer->getTable('cataloginventory/stock_item');

$connection->modifyColumn($tableName,'pickup_sales_unit', 'DECIMAL(7,2) NOT NULL DEFAULT \'1\'');
$connection->modifyColumn($tableName,'delivery_sales_unit', 'DECIMAL(7,2) NOT NULL DEFAULT \'1\'');

$installer->run("
    UPDATE $tableName SET pickup_sales_unit   = 1000 WHERE pickup_sales_unit   >= 999;
    UPDATE $tableName SET delivery_sales_unit = 1000 WHERE delivery_sales_unit >= 999;
");

$installer->endSetup();

?>
