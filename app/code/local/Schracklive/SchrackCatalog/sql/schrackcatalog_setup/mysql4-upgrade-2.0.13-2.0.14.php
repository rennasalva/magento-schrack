<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$entityTypeId     = $installer->getEntityTypeId('catalog_product');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$productTableName = $this->getTable('catalog_product_entity');

$installer->startSetup();

/* protobufs:
  message Article {
...
        message VPEs {
            required string vpe = 1;
            repeated VPE vpes = 2;
        }

        message VPE {
            required int32 quantity = 1;
            required bool salable = 2;
            required bool conveyable = 3;
        }
...
        optional int32 pceve = 27;      			//2014-08-06 MHR: Anzahl der Stück bei Artikeln mit Mengeneinheit VE
        repeated VPEs vpes = 28;                    //2014-08-06 MHR: Liste aller Verpackungsvarianten
        optional string mainsection = 29;			//2014-08-28 MHR: Artikel-Kapitel-Hauptzuordnung
        repeated string accessoriesnecessary = 31;  //2014-09-01 MHR: Notwenndiges Zubehör.
        repeated string accessoriesoptional = 32;   //2014-09-01 MHR: Optionales Zubehör.
        optional string longtextaddition = 34;      //2014-09-16 MHR: Artikel Lang Text Zusatz.
...
*/

$installer->addAttribute('catalog_product', 'schrack_vpes', array(
    'type'              => 'varchar',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'VPEs',
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
    'visible_on_front'  => true,
    'unique'            => false,
    'is_configurable'   => true
));

$installer->run("
  ALTER TABLE {$this->getTable('catalog_product_entity')} ADD `schrack_main_category_id` int(10) unsigned DEFAULT NULL;
");
$installer->addAttribute('catalog_product', 'schrack_main_category_id', array(
    'type'              => 'static',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Main category id',
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
    'visible_on_front'  => true,
    'unique'            => false,
    'is_configurable'   => true
));

$installer->addAttribute('catalog_product', 'schrack_accessories_necessary', array(
    'type'              => 'varchar',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Necessary accessories',
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
    'visible_on_front'  => true,
    'unique'            => false,
    'is_configurable'   => true
));

$installer->addAttribute('catalog_product', 'schrack_accessories_optional', array(
    'type'              => 'varchar',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Optional accessories',
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
    'visible_on_front'  => true,
    'unique'            => false,
    'is_configurable'   => true
));

$installer->addAttribute('catalog_product', 'schrack_long_text_addition', array(
    'type'              => 'varchar',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Long text addition',
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
    'visible_on_front'  => true,
    'unique'            => false,
    'is_configurable'   => true
));

$installer->run("
  ALTER TABLE {$this->getTable('catalog_product_entity')} ADD `schrack_qty_per_packaging_unit` int(10) unsigned DEFAULT NULL;
");
$installer->addAttribute('catalog_product', 'schrack_qty_per_packaging_unit', array(
    'type'              => 'static',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Main category id',
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
    'visible_on_front'  => true,
    'unique'            => false,
    'is_configurable'   => true
));

$installer->addAttributeToGroup($entityTypeId,$attributeSetId,$attributeGroupId,'schrack_vpes',210);
$installer->addAttributeToGroup($entityTypeId,$attributeSetId,$attributeGroupId,'schrack_main_category_id',211);
$installer->addAttributeToGroup($entityTypeId,$attributeSetId,$attributeGroupId,'schrack_accessories_necessary',212);
$installer->addAttributeToGroup($entityTypeId,$attributeSetId,$attributeGroupId,'schrack_accessories_optional',213);
$installer->addAttributeToGroup($entityTypeId,$attributeSetId,$attributeGroupId,'schrack_long_text_addition',214);
$installer->addAttributeToGroup($entityTypeId,$attributeSetId,$attributeGroupId,'schrack_qty_per_packaging_unit',215);

$installer->endSetup();