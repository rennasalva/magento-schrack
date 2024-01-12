<?php

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$tableName = $installer->getTable('cataloginventory/stock');

$connection->modifyColumn($tableName,'delivery_hours', 'SMALLINT(3) NOT NULL DEFAULT \'0\'');

$installer->endSetup();

?>
