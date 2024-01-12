<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

try {
    $installer->getConnection()->beginTransaction();
    $installer->run ("
      INSERT INTO catalog_product_entity_text (entity_type_id, attribute_id, store_id, entity_id, `value`)
      SELECT entity_type_id, attribute_id, store_id, entity_id, `value` FROM catalog_product_entity_varchar
      WHERE attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'schrack_long_text_addition');

      UPDATE eav_attribute SET backend_type = 'text' WHERE attribute_code = 'schrack_long_text_addition';

      DELETE FROM catalog_product_entity_varchar WHERE attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'schrack_long_text_addition');
    ");
    $installer->getConnection()->commit();
}
catch ( Exception $ex ) {
    $installer->getConnection()->rollback();
    throw $ex;
}

$installer->endSetup();

