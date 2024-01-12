<?php

$installer = $this;
$installer->startSetup();
$entityTypeId = $installer->getEntityTypeId('catalog_category');

if ( ! $installer->getAttribute($entityTypeId, 'schrack_strategic_pillar') ) {
    $installer->addAttribute('catalog_category', 'schrack_strategic_pillar', array(
        'type' => 'varchar',
        'label' => 'Strategic Pillar',
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