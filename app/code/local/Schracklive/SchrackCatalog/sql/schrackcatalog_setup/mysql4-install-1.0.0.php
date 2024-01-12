<?php

$installer = $this;

$installer->startSetup();

$installer->addAttribute('catalog_product', 'schrack_category_names', array(
        'type'              => 'static',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Kategorienamen',
        'input'             => 'text',
        'class'             => '',
        'source'            => '',
        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        'visible'           => true,
        'required'          => false,
        'user_defined'      => false,
        'default'           => '',
        'searchable'        => true,
        'filterable'        => true,
        'comparable'        => false,
        'visible_on_front'  => false,
        'unique'            => false,
        'is_configurable'   => false
    ));

$installer->run("

ALTER TABLE {$this->getTable('catalog_product_entity')} ADD `schrack_category_names` varchar(255) NOT NULL default '';

    ");

$installer->endSetup();

?>
