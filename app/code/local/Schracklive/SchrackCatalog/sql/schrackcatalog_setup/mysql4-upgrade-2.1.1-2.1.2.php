<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', '0', 'schrack/general/default_cutting_costs', '25');
");

$installer->endSetup();

