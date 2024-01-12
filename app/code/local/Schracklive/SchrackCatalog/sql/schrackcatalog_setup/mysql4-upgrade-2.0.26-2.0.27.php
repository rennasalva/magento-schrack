<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE catalog_category_product ADD COLUMN schrack_sts_is_accessory int(1) NOT NULL DEFAULT 0 AFTER position;
");

$installer->endSetup();

