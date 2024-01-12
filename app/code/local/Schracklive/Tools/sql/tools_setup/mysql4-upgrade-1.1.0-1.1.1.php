<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

INSERT INTO core_url_rewrite (store_id, category_id, product_id, id_path, request_path, target_path, is_system, options, description)
VALUES( '1', NULL, NULL, '4711_0815', 'onlinetools/schracklwl', 'onlinetools/easylanConfigurator', '0', NULL, 'alternate url onlinetools/schracklwl for Easylan configurator');

");

$installer->endSetup();