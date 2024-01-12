<?php

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$tableName = $installer->getTable('sales/quote');
$connection->addColumn($tableName,'is_pickup', 'TINYINT(1) NOT NULL DEFAULT \'0\'');

$installer->endSetup();

?>
