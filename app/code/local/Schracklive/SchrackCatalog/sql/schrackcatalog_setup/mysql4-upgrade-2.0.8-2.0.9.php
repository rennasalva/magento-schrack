<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("UPDATE catalog_eav_attribute SET is_visible_on_front = 1 WHERE attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 4 and attribute_code = 'schrack_productgroup');");

$installer->endSetup();

