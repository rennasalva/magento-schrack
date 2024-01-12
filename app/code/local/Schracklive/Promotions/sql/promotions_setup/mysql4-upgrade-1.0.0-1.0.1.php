<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE schrack_promotion ADD COLUMN created_at datetime NULL DEFAULT NULL;
ALTER TABLE schrack_promotion_account ADD COLUMN created_at datetime NULL DEFAULT NULL;
ALTER TABLE schrack_promotion_account_customer ADD COLUMN created_at datetime NULL DEFAULT NULL;
ALTER TABLE schrack_promotion_product ADD COLUMN created_at datetime NULL DEFAULT NULL;
ALTER TABLE schrack_promotion_account_product ADD COLUMN created_at datetime NULL DEFAULT NULL;
");

$installer->endSetup();
