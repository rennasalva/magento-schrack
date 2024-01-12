<?php 

$installer = $this;

$installer->startSetup();
$connection = $installer->getConnection();

$tableName = $installer->getTable($installer->getTable('geoip_log'));

$connection->addColumn($tableName, 'user_country_id', 'VARCHAR(3)');

$installer->endSetup(); 

