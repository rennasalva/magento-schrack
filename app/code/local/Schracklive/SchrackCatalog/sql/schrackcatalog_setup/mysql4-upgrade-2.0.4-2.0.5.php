<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$entityTypeId     = $installer->getEntityTypeId('catalog_product');
$attributeSetId   = $installer->getAttributeSetId($entityTypeId,'Schrack');
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$productTableName = $this->getTable('catalog_product_entity');

$installer->startSetup();

$installer->run("

    UPDATE catalog_eav_attribute SET is_visible_on_front = 1  
    WHERE attribute_id IN (SELECT attribute_id FROM eav_attribute 
                           WHERE entity_type_id = $entityTypeId
                           AND attribute_code IN ('schrack_ean', 
                                                   'meta_keyword', 
                                                   'description', 
                                                   'meta_description', 
                                                   'short_description', 
                                                   'schrack_detail_description', 
                                                   'schrack_detail_description_title', 
                                                   'schrack_keyword_foreign')
                          )

");


$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'schrack_keyword_foreign',
    201
);

$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'schrack_keyword_foreign_hidden',
    202
);

$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'schrack_detail_description_title',
    203
);

$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'schrack_detail_description',
    205
);

$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'schrack_is_cable',
    206
);


$installer->endSetup();

