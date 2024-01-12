<?php

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$conn->addColumn($installer->getTable('schrackwishlist/partslist_item'), 'schrack_price', 'DECIMAL(12,4) NULL DEFAULT NULL');
$conn->addColumn($installer->getTable('schrackwishlist/partslist_item'), 'schrack_basic_price', 'DECIMAL(12,4) NULL DEFAULT NULL');
$conn->addColumn($installer->getTable('schrackwishlist/partslist_item'), 'schrack_surcharge', 'DECIMAL(12,4) NULL DEFAULT NULL');

$installer->endSetup();
