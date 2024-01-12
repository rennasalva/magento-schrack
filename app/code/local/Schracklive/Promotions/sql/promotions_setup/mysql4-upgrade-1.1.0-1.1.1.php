<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data SET `path` = 'schrack/promotions/log_detailed_customer_id', `value` = '';   
");

$installer->endSetup();
