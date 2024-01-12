<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$entityTypeId     = $installer->getEntityTypeId('catalog_product');
$productTableName = $this->getTable('catalog_product_entity');

$installer->startSetup();

$installer->run("
    ALTER TABLE catalog_product_entity ADD COLUMN schrack_main_producer varchar(64) AFTER schrack_sts_green_stamp;
");

$sql = "SELECT frontend_input, attribute_id FROM eav_attribute WHERE attribute_code = 'schrack_hersteller';";
$rows =  Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);
$frontendInput = $rows[0]['frontend_input'];
$attributeId = $rows[0]['attribute_id'];
if ( $frontendInput == 'multiselect' ) {
    $installer->run("
        UPDATE catalog_product_entity e 
        LEFT JOIN catalog_product_entity_varchar a ON e.entity_id = a.entity_id AND a.attribute_id = $attributeId
        LEFT JOIN eav_attribute_option o ON SUBSTRING_INDEX(a.value,',',1) = o.option_id
        LEFT JOIN eav_attribute_option_value v ON o.option_id = v.option_id AND v.store_id = 0
        SET e.schrack_main_producer = v.value;
    ");
} else {
    $installer->run("
        UPDATE catalog_product_entity e 
        LEFT JOIN catalog_product_entity_varchar a ON e.entity_id = a.entity_id AND a.attribute_id = $attributeId
        SET e.schrack_main_producer = a.value;
    ");
}

$installer->endSetup();

