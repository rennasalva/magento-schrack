<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

// Just removing all the wrong required-flags
$installer->run("
    UPDATE eav_attribute SET is_required = 0 WHERE attribute_code IN 
    ('schrack_sts_green_stamp',  'schrack_sts_main_article_sku', 'schrack_sts_main_supplier',              'schrack_sts_main_vpe_size',
     'schrack_sts_main_vpe_type','schrack_sts_managed_inventory','schrack_sts_printed_manufacturer_number','schrack_sts_sub_article_skus',
     'schrack_sts_valid_until',  'schrack_wws_ranking');
");

$installer->endSetup();

