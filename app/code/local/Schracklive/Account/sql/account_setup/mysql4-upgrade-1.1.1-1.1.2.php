<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('account')} ADD `gtc_accepted` tinyint(4) NOT NULL default '0';

    ");

$installer->endSetup();
