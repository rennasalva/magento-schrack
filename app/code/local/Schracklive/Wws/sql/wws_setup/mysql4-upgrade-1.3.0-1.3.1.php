<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE `{$installer->getTable('wws_signal')}` ADD `change_drop` tinyint(1) NOT NULL default '0';
ALTER TABLE `{$installer->getTable('wws_signal')}` ADD `ship_drop` tinyint(1) NOT NULL default '0';

    ");

$installer->endSetup();
