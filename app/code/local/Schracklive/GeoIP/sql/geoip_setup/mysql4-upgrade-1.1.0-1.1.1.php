<?php 

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$trackingTable = $conn->newTable($installer->getTable('geoip_log'))
        ->addColumn('log_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'identity'  => true,
                'auto_increment' => true,
                'nullable'  => false,
                'primary'   => true,
                'unsigned'  => true,
            ), 'Primary Key ID')
        ->addColumn('source_host', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                'nullable'  => true,
                'default' => null,
            ))
        ->addColumn('source_uri', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                'nullable'  => true,
                'default' => null,
            ))
        ->addColumn('referer', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                'nullable'  => true,
                'default' => null,
            ))
        ->addColumn('target_country_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 3, array(
                'nullable'  => true,
                'default' => null,
            ))
        ->addColumn('schrack_wws_customer_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 6, array(
                'nullable'  => true,
                'unsigned'  => true,
                'default'   => null,
            ))
        ->addColumn('schrack_wws_contact_number', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
                'nullable'  => true,
                'default'   => null,
            ))
        ->addIndex('IDX_GEOIPLOG_TARGET_COUNTRY_ID', array('target_country_id'))
        ->addIndex('IDX_GEOIPLOG_SCHRACK_WWS_CUSTOMER_ID', array('schrack_wws_customer_id', 'schrack_wws_contact_number'))
;
$conn->createTable($trackingTable);
$conn->changeColumn($installer->getTable('geoip_log'), 'log_id', 'log_id', 'INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT'); // Duh! Varien_Db_Ddl_Table HAS an option for auto-increment, but the Mysql adapter does not heed it
$conn->addColumn($installer->getTable('geoip_log'), 'created_at', 'datetime default NULL AFTER schrack_wws_contact_number'); // duh, there is no datetime in Varien_Db_Ddl_Table

$installer->endSetup(); 

