<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('account')} ADD `sales_area` smallint(3) NOT NULL default 0;
ALTER TABLE {$this->getTable('account')} ADD `rating` char(1) NOT NULL default '';
ALTER TABLE {$this->getTable('account')} ADD `enterprise_size` char(1) NOT NULL default '';
ALTER TABLE {$this->getTable('account')} ADD `account_type` varchar(255) NOT NULL default '';

    ");

$installer->endSetup();
