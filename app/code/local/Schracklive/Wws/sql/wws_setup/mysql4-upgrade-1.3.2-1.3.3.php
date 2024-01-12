<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("
    delete from core_config_data where path = 'schrack/wws/pullMeinhartQty';
    insert into core_config_data set scope=default, scope_id=0, path= 'schrack/wws/pullMeinhartQty', value='1';
");

$installer->endSetup();
