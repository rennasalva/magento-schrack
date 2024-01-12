<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('account')} ADD `vat_identification_number` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('account')} ADD `company_registration_number` varchar(255) NOT NULL default '';

    ");

$installer->endSetup();
