<?php

/*
 * changes for multiple wishlists, plus possibility for users to set their own numbers (was overridden to 1)
 */

$installer = $this;

$installer->startSetup();

$wishlistTableName = $installer->getTable('wishlist/wishlist');
$wishlistItemTableName = $installer->getTable('wishlist/item');
$conn = $installer->getConnection();

// multiple wishlists need to be named
$conn->addColumn($wishlistTableName, 'description', 'text NULL DEFAULT NULL');
$installer->run("
    UPDATE `{$wishlistTableName}` SET `description` = '" . Mage::helper('page')->__('Default Wishlist') . "'
");
    
/* un-uniquify customer idx, because we'll have multiple wishlists per customer */    
$conn->dropForeignKey($wishlistTableName, 'FK_WISHLIST_CUSTOMER');
$conn->dropKey($wishlistTableName, 'UNQ_CUSTOMER');
$conn->addKey($wishlistTableName, 'IDX_CUSTOMER', array('customer_id'));
$conn->addConstraint('FK_WISHLIST_CUSTOMER', $wishlistTableName, 'customer_id', $installer->getTable('customer_entity'), 'entity_id');

$conn->changeColumn($wishlistItemTableName, 'qty', 'qty', 'DECIMAL(12,4) NULL DEFAULT NULL');



$installer->endSetup();