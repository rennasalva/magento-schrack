<?php 

$installer = $this;

$installer->startSetup();

// did this manually due to deployment f'up

// $connection = $installer->getConnection();

// $tableName = $installer->getTable($installer->getTable('geoip_log'));

// $connection->addColumn($tableName, 'user_ip', 'VARCHAR(100)');
// $connection->addColumn($tableName, 'user_agent', 'VARCHAR(255)');

$installer->endSetup(); 

