<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();
$productTableName = $this->getTable('catalog_product_entity');

$installer->run("
    ALTER TABLE {$productTableName} ADD COLUMN schrack_main_category_eid INT(10) UNSIGNED AFTER schrack_main_category_id;
    DROP TABLE schrack_category_reverse_group_id;
");

$installer->addAttribute('catalog_product', 'schrack_main_category_eid', array(
    'type' => 'static',
    'label' => 'schrack_main_category_eid',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'required' => false,
    'user_defined' => true,
    'default' => '',
    'searchable' => true,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => true,
    'unique' => false,
    'is_configurable' => false
));

$categoryMap = Schracklive_SchrackCatalog_Model_Category::getIdMap();

$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
$writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

$sql = " SELECT entity_id, schrack_main_category_id AS cat_id FROM catalog_product_entity"
     . " WHERE schrack_sts_statuslocal NOT IN ('tot', 'strategic_no')"
     . " AND schrack_main_category_id > ' '";
$dbRres = $readConnection->fetchAll($sql);
$productMap = array();
foreach ( $dbRres as $row ) {
    $entityID = $row['entity_id'];
    $schrackID = $row['cat_id'];
    if ( ! isset($productMap[$schrackID]) ) {
        $productMap[$schrackID] = array();
    }
    $productMap[$schrackID][] = $entityID;
}

$writeConnection->beginTransaction();
try {
    foreach ( $productMap as $schrackCatId => $entityIdArray ) {
        $sql = " UPDATE catalog_product_entity "
             . " SET schrack_main_category_eid = '" . $categoryMap[$schrackCatId] . "'"
             . " WHERE entity_id IN (" . implode(',',$entityIdArray) . ")";
        $writeConnection->query($sql);
    }
    $writeConnection->commit();
} catch ( Exception $ex ) {
    $writeConnection->rollback();
    throw $ex;
}

unset($categoryMap);
unset($productMap);

$installer->endSetup();

