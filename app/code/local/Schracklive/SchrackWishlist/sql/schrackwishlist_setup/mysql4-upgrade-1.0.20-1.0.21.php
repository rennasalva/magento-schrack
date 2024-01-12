<?php

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$partslistTable = $installer->getTable('partslist');

$conn->addColumn($partslistTable, 'updated_by', 'INT UNSIGNED NULL DEFAULT NULL AFTER updated_at'); // nullabe, because initially we don't know 'em
$conn->addKey($partslistTable, 'IDX_UPDATED_BY', 'updated_by');
$conn->addConstraint('FK_PARTSLIST_UPDATED_BY', $partslistTable, 'updated_by',
        $installer->getTable('customer_entity'), 'entity_id');

$partslistItemTable = $installer->getTable('partslist_item');

$conn->addColumn($partslistItemTable, 'updated_by', 'INT UNSIGNED NULL DEFAULT NULL'); // nullabe, because initially we don't know 'em
$conn->addKey($partslistItemTable, 'IDX_UPDATED_BY', 'updated_by');
$conn->addConstraint('FK_PARTSLIST_ITEM_UPDATED_BY', $partslistItemTable, 'updated_by',
        $installer->getTable('customer_entity'), 'entity_id');

$installer->endSetup();