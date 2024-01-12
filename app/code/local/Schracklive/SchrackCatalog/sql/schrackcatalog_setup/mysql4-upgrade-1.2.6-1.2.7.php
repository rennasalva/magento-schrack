<?php

$installer = $this;
$installer->startSetup();
$entityTypeId = $installer->getEntityTypeId('catalog_product');

if (!$installer->getAttribute($entityTypeId, 'schrack_references')) {
	$installer->addAttribute('catalog_product', 'schrack_references', array(
		'type' => 'static',
		'label' => 'schrack_references',
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
	$installer->run("ALTER TABLE {$this->getTable('catalog_product_entity')} ADD `schrack_references` varchar(255) NOT NULL default '';");
}

$installer->endSetup();

?>