<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('account')} ADD COLUMN `wws_customer_id_history` TEXT DEFAULT NULL AFTER `wws_customer_id`, ADD COLUMN `schrack_s4y_id_history` TEXT DEFAULT NULL AFTER `schrack_s4y_id`;

    ");

$installer->endSetup();
