<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$entityTypeId     = $installer->getEntityTypeId('catalog_product');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$productTableName = $this->getTable('catalog_product_entity');

$installer->startSetup();


$installer->addAttribute('catalog_product', 'schrack_detail_description_title', array(
        'type'              => 'text',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Detailed description title',
        'input'             => 'text',
        'class'             => '',
        'source'            => '',
        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        'visible'           => true,
        'required'          => false,
        'user_defined'      => true,
        'default'           => '',
        'searchable'        => false,
        'filterable'        => false,
        'comparable'        => false,
        'visible_on_front'  => false,
        'unique'            => false,
        'is_configurable'   => true
    ));

$installer->addAttribute('catalog_product', 'schrack_detail_description', array(
        'type'              => 'text',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Detailed description',
        'input'             => 'text',
        'class'             => '',
        'source'            => '',
        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        'visible'           => true,
        'required'          => false,
        'user_defined'      => true,
        'default'           => '',
        'searchable'        => false,
        'filterable'        => false,
        'comparable'        => false,
        'visible_on_front'  => false,
        'unique'            => false,
        'is_configurable'   => true
    ));

$installer->addAttribute('catalog_product', 'schrack_is_cable', array(
    'type' => 'static',
    'label' => 'schrack_is_cable',
    'input' => 'int',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => true,
    'user_defined' => true,
    'default' => '',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'is_configurable' => false
));
$installer->run("ALTER TABLE {$productTableName} ADD schrack_is_cable INT(1) NOT NULL DEFAULT 0 AFTER schrack_sortiment;");
$installer->run("
        UPDATE {$productTableName} AS prod 
        JOIN catalog_product_entity_varchar attr ON prod.entity_id = attr.entity_id  
        SET prod.schrack_is_cable = IF(attr.value = 't',1,0)
        WHERE attr.attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 4 AND attribute_code = 'schrack_kabel');
");


$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'schrack_detail_description_title',
    117
);

$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'schrack_detail_description',
    118
);

$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'schrack_is_cable',
    119
);

$installer->endSetup();

