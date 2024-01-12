<?php

$installer = $this;
$installer->startSetup();

$entityTypeId = $installer->getEntityTypeId('catalog_product');
$attributeSetId = $installer->getAttributeSetId('catalog_product', Schracklive_SchrackCatalog_Model_Import::STANDARD_ATTRIBUTESET_NAME);
$attributeGroupId = $installer->getAttributeGroupId($entityTypeId, $attributeSetId, 'General');

$installer->updateAttribute($entityTypeId, 'schrack_url_key_without_sku', 'frontend_label', 'URL Key Without SKU');
$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'schrack_url_key_without_sku',
	100
);

$installer->endSetup();