<?php

/*
 * one wishlist per customer will be our default wishlist
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();
$logTable = $conn->newTable($installer->getTable('schrackcustomer/customer_log'))
        ->addColumn('log_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'identity'  => true,
                'auto_increment' => true,
                'nullable'  => false,
                'primary'   => true,
                'unsigned'  => true,
            ), 'Primary Key ID')
        ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'nullable'  => true,
                'unsigned'  => true,
                'default'   => null,
            ))
        ->addColumn('entity_name', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                'nullable'  => false,
            ), 'Name of the table')
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                'nullable'  => false,
            ), 'ID in the table')
        ->addColumn('action', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                'nullable'  => false,
                'unsigned'  => true,
                'default'   => '0',
            ))
        ->addColumn('description', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                'nullable'  => true,
                'default'   => null,
            ))
        ->addIndex('IDX_CUSTOMER', array('customer_id'))
        ->addForeignKey('FK_PARTSLIST_CUSTOMER', 'customer_id', $installer->getTable('customer_entity'), 'entity_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
;        
$conn->createTable($logTable);
$conn->changeColumn($installer->getTable('schrackcustomer/customer_log'), 'log_id', 'log_id', 'INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT'); // Duh! Varien_Db_Ddl_Table HAS an option for auto-increment, but the Mysql adapter does not heed it
$conn->addColumn($installer->getTable('schrackcustomer/customer_log'), 'updated_at', 'datetime default NULL AFTER description'); // duh, there is no datetime in Varien_Db_Ddl_Table

$installer->endSetup();