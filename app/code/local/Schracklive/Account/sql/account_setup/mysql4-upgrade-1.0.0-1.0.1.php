<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('account')} ADD `advisor_principal_name` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('account')} ADD `advisors_principal_names` text NOT NULL default '';

ALTER TABLE {$this->getTable('account')} DROP `advisor_id`;

    ");

$installer->endSetup();
