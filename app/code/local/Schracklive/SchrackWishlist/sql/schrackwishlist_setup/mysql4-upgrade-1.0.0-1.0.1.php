<?php

/*
 * one wishlist per customer will be our default wishlist
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();
$partslistTable = $conn->newTable($installer->getTable('schrackwishlist/partslist'))
        ->addColumn('partslist_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'identity'  => true,
                'auto_increment' => true,
                'nullable'  => false,
                'primary'   => true,
                'unsigned'  => true,
            ), 'Primary Key ID')
        ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'nullable'  => false,
                'unsigned'  => true,
                'default'   => '0',
            ))
        ->addColumn('is_active', Varien_Db_Ddl_Table::TYPE_TINYINT, null, array(
                'nullable'  => false,
                'unsigned'  => true,
                'default'   => '0',
            ))
        ->addColumn('description', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                'nullable'  => true,
                'default'   => 'My Partslist',
            ))
        ->addIndex('IDX_CUSTOMER', array('customer_id'))
        ->addForeignKey('FK_PARTSLIST_CUSTOMER', 'customer_id', $installer->getTable('customer_entity'), 'entity_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
;        
$conn->createTable($partslistTable);
$conn->changeColumn($installer->getTable('schrackwishlist/partslist'), 'partslist_id', 'partslist_id', 'INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT'); // Duh! Varien_Db_Ddl_Table HAS an option for auto-increment, but the Mysql adapter does not heed it
$conn->addColumn($installer->getTable('schrackwishlist/partslist'), 'updated_at', 'datetime default NULL AFTER is_active'); // duh, there is no datetime in Varien_Db_Ddl_Table



$partslistItemTable = $conn->newTable($installer->getTable('schrackwishlist/partslist_item'))
        ->addColumn('partslist_item_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'identity'  => true,
                'auto_increment' => true,
                'nullable'  => false,
                'primary'   => true,
                'unsigned'  => true,
            ), 'Primary Key ID')
        ->addColumn('partslist_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'nullable'  => false,
                'unsigned'  => true,
            ), 'Ref to Partslist')
        ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'nullable'  => false,
                'unsigned'  => true,
                'default'   => '0',
            ))
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
                'nullable'  => true,
                'unsigned'  => true,
                'default'   => null,
            ))
        ->addColumn('description', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                'nullable'  => true,
                'default'   => null,
            ))
        ->addColumn('qty', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(12,4), array(
                'nullable'  => true,
                'default'   => null,
            ))
        ->addIndex('IDX_PARTSLIST', array('partslist_id'))
        ->addIndex('IDX_PRODUCT', array('product_id'))
        ->addIndex('IDX_STORE', array('store_id'))
        ->addForeignKey('FK_PARTSLIST_ITEM_PARTSLIST', 'partslist_id', $installer->getTable('schrackwishlist/partslist'), 'partslist_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey('FK_PARTSLIST_ITEM_PRODUCT', 'product_id', $installer->getTable('catalog_product_entity'), 'entity_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey('FK_PARTSLIST_ITEM_STORE', 'store_id', $installer->getTable('core_store'), 'store_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
;        
$conn->createTable($partslistItemTable);
$conn->changeColumn($installer->getTable('schrackwishlist/partslist_item'), 'partslist_item_id', 'partslist_item_id', 'INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT'); // Duh! Varien_Db_Ddl_Table HAS an option for auto-increment, but the Mysql adapter does not heed it
$conn->addColumn($installer->getTable('schrackwishlist/partslist_item'), 'added_at', 'datetime default NULL AFTER store_id'); // duh, there is no datetime in Varien_Db_Ddl_Table


//// revert changes on wishlist, there can only be one!
$conn->dropColumn($installer->getTable('wishlist/wishlist'), 'description');
$conn->dropForeignKey($installer->getTable('wishlist/wishlist'), 'FK_WISHLIST_CUSTOMER');
$conn->dropKey($installer->getTable('wishlist/wishlist'), 'IDX_CUSTOMER');
$installer->run("
     DELETE FROM wishlist WHERE customer_id IN (SELECT customer_id FROM (SELECT COUNT(*) c,customer_id FROM wishlist GROUP BY customer_id HAVING c>1 LIMIT 1) x);
");
$conn->addKey($installer->getTable('wishlist/wishlist'), 'UNQ_CUSTOMER', 'customer_id', 'UNIQUE');
$conn->addConstraint('FK_WISHLIST_CUSTOMER', $installer->getTable('wishlist/wishlist'), 'customer_id', $installer->getTable('customer_entity'), 'entity_id');
    
$installer->endSetup();