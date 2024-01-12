<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("
ALTER TABLE {$this->getTable('translation')} ADD `is_orphaned` tinyint(1) unsigned NOT NULL DEFAULT '0';
");
//UPDATE {$this->getTable('core_config')} SET `value` = '' WHERE `key` = '';

$installer->endSetup();
