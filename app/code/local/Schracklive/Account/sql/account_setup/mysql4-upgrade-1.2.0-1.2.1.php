<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('account')} ADD `match_code` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('account')} ADD `description` mediumtext NOT NULL default '';
ALTER TABLE {$this->getTable('account')} ADD `information` mediumtext NOT NULL default '';

    ");

$installer->endSetup();

