<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('account')} DROP `street`;
ALTER TABLE {$this->getTable('account')} DROP `postcode`;
ALTER TABLE {$this->getTable('account')} DROP `city`;
ALTER TABLE {$this->getTable('account')} DROP `country_id`;

    ");

$installer->endSetup();
