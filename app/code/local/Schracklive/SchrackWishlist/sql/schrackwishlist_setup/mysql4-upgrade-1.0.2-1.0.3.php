<?php

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$conn->addColumn($installer->getTable('schrackwishlist/partslist'), 'comment', 'TEXT NULL DEFAULT NULL');

$installer->endSetup(); 
