<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('account')} ADD `crm_status` varchar(255) NOT NULL default '';

    ");

$installer->endSetup();
