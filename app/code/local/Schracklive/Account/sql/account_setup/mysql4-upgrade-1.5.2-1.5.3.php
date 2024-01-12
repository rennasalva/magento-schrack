<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('account')} ADD `limit_web` int(4) default NULL;

    ");

$installer->endSetup();
