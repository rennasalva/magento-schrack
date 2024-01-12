<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

try {
    $installer->getConnection()->beginTransaction();
    $installer->run ("
        UPDATE catalog_eav_attribute cat
        JOIN eav_attribute main ON main.attribute_id = cat.attribute_id
        SET used_in_product_listing = 1 
        WHERE main.entity_type_id = 4 AND backend_type <> 'static' AND attribute_code IN ('name','schrack_vpes','schrack_long_text_addition');
        
        UPDATE catalog_eav_attribute cat
        JOIN eav_attribute main ON main.attribute_id = cat.attribute_id
        SET used_in_product_listing = 0 
        WHERE main.entity_type_id = 4 AND backend_type <> 'static' AND attribute_code NOT IN ('name','schrack_vpes','schrack_long_text_addition');
    ");
    $installer->getConnection()->commit();
}
catch ( Exception $ex ) {
    $installer->getConnection()->rollback();
    throw $ex;
}

$installer->endSetup();

