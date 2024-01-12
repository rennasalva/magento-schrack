<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('account')} ADD `created_by` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('account')} ADD `updated_by` varchar(255) NOT NULL default '';

ALTER TABLE {$this->getTable('account_address')} ADD `phone1` varchar(30) NOT NULL default '';
ALTER TABLE {$this->getTable('account_address')} ADD `phone2` varchar(30) NOT NULL default '';
ALTER TABLE {$this->getTable('account_address')} ADD `fax` varchar(30) NOT NULL default '';
ALTER TABLE {$this->getTable('account_address')} ADD `created_by` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('account_address')} ADD `updated_by` varchar(255) NOT NULL default '';

    ");

$installer->endSetup();
