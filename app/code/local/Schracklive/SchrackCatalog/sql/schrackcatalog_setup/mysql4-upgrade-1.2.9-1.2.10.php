<?php

$installer = $this;
$installer->startSetup();
$entityTypeId = $installer->getEntityTypeId('catalog_product');

if (!$installer->getAttribute($entityTypeId, 'schrack_url_key_without_sku')) {
	$installer->addAttribute('catalog_product', 'schrack_url_key_without_sku', array(
		'type' => 'varchar',
		'label' => 'schrack_url_key_without_sku',
		'input' => 'text',
		'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
		'required' => false,
		'user_defined' => true,
		'default' => '',
		'searchable' => true,
		'filterable' => true,
		'comparable' => false,
		'visible_on_front' => false,
		'unique' => false,
		'is_configurable' => false
	));
}

$installer->endSetup();

?>