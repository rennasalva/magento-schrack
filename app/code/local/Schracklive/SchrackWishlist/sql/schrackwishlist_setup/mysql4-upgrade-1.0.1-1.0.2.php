<?php

/*
 * one wishlist per customer will be our default wishlist
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$partslistItemOptionTable = $conn->newTable($installer->getTable('schrackwishlist/partslist_item_option'))
        ->addColumn('option_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'identity'  => true,
                'auto_increment' => true,
                'nullable'  => false,
                'primary'   => true,
                'unsigned'  => true,
            ), 'Primary Key ID')
        ->addColumn('partslist_item_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'nullable'  => false,
                'unsigned'  => true,
            ), 'Ref to Partslist Item')
        ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'nullable'  => false,
                'unsigned'  => true,
                'default'   => '0',
            ))
        ->addColumn('code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                'nullable'  => false,
            ))
        ->addIndex('IDX_PARTSLIST_ITEM_OPTION_ITEM_ID', array('partslist_item_id'))
        ->addForeignKey('FK_PARTSLIST_ITEM_OPTION_ITEM_ID', 'partslist_item_id', $installer->getTable('schrackwishlist/partslist_item'), 'partslist_item_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
;        
$conn->createTable($partslistItemOptionTable);
$conn->changeColumn($installer->getTable('schrackwishlist/partslist_item_option'), 'option_id', 'option_id', 'INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT'); // Duh! Varien_Db_Ddl_Table HAS an option for auto-increment, but the Mysql adapter does not heed it
$conn->addColumn($installer->getTable('schrackwishlist/partslist_item_option'), 'value', 'text NOT NULL AFTER code'); // duh, there is no text in Varien_Db_Ddl_Table!

$installer->endSetup(); 
