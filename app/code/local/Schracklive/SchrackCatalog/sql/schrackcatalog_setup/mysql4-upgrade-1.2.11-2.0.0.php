<?php
$staticAttrsToRenameOld2new = array(
    'schrack_references' => 'schrack_substitute',
    'schrack_spec_vpe'   => 'schrack_packingunit',
    'schrack_spec_pe'    => 'schrack_priceunit',
    'schrack_spec_me'    => 'schrack_qtyunit'
);

$staticAttrsToRenameNew2old = array();

foreach ( $staticAttrsToRenameOld2new as $old => $new ) {
    $staticAttrsToRenameNew2old[$new] = $old;
}

$staticAttrsToStay = array(
    'sku',
    'attribute_set_id',
    'created_at',
    'entity_id',
    'entity_type_id',
    'has_options',
    'required_options',
    'type_id',
    'updated_at',
    'schrack_catalognr',
    'schrack_ean',
    'schrack_productgroup',
    'schrack_sortiment',
    'schrack_substitute',
    'schrack_packingunit',
    'schrack_priceunit',
    'schrack_qtyunit',
    'schrack_references',
    'schrack_spec_vpe',
    'schrack_spec_pe',
    'schrack_spec_me'
);

$staticAttrsToRemove = array();

$staticToDynamicAttrs = array(
    'description'       => false,
    'short_description' => false,
    'meta_description'  => false,
    'meta_title'        => false
);

$staticAttrSqlTypes = array(
    'description'       => 'text',
    'short_description' => 'text',
    'meta_description'  => 'varchar(255)',
    'meta_title'        => 'varchar(255)'
);

$dynamicAttrsToRemove = array();

$installer = $this;
$installer->startSetup();

$code2attrMap = array();

$attrTableName = $this->getTable('eav/attribute');
$entityTableName = $installer->getTable('catalog_product_entity');
$entityTypeId = $installer->getEntityTypeId('catalog_product');

// analyze existing attribute situation:
$attributes = Mage::getResourceModel('catalog/product_attribute_collection')->getItems();
foreach ( $attributes as $attribute ) {
    $code = $attribute->getAttributeCode();
    $type = $attribute->getBackendType();
    $code2attrMap[$code] = $attribute;
    if ( $type === 'static' ) {
        if ( array_key_exists($code,$staticToDynamicAttrs) ) {
            $staticToDynamicAttrs[$code] = true;
        }
        if ( array_key_exists($code,$staticAttrsToRenameNew2old) && ! array_key_exists($code,$staticAttrsToRenameOld2new) ) {
            // already renamed:
            $old = $staticAttrsToRenameNew2old[$code];
            unset($staticAttrsToRenameOld2new[$old]);
            unset($staticAttrsToRenameNew2old[$code]);
        }
        if ( ! array_key_exists($code,$staticToDynamicAttrs) && ! in_array($code,$staticAttrsToStay) && ! array_key_exists($code,$staticAttrsToRenameOld2new) ) {
            $staticAttrsToRemove[] = $code;
        }
        if ( array_key_exists($code,$staticAttrsToRenameNew2old) ) {
            $staticAttrsToRemove[] = $code;
        }
    }
    else {
        if ( strpos($code,'schrack') === 0 ) {
            $dynamicAttrsToRemove[] = $code;
        }
    }
}

// move static to dynamic:
foreach ( $staticToDynamicAttrs as $code => $flag ) {
    if ( $flag ) {
        $oldAttr = $code2attrMap[$code];
        $tmpCode = $code . '_old';

        //// 1st: rename old attr to *_old
        $installer->updateAttribute('catalog_product', $code, array('attribute_code' => $tmpCode));
        unset($this->_setupCache[$attrTableName][$entityTypeId][$code]); // because bloody magento does not actualize the setup cache in function above
        $type = $staticAttrSqlTypes[$code];
        $installer->run("ALTER TABLE {$entityTableName} CHANGE {$code} {$tmpCode} {$type}");
        
        //// 2nd: create new attr with original name
        if ( ($pos = strpos($type,'(')) > 1 ) {
            $type = substr($type,0,$pos);
        }
        $attrData = array(
            'type' => $type,
            'label' => $oldAttr->getFrontendLabel(),
            'input' => $oldAttr->getFrontendInput(),
            'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
            'required' => $oldAttr->getIsRequired(),
            'user_defined' => false,
            'default' => '',
            'searchable' => true,
            'filterable' => true,
            'comparable' => false,
            'visible_on_front' => false,
            'unique' => false,
            'is_configurable' => false
        );
        $installer->addAttribute('catalog_product', $code, $attrData);
        $attrId = $installer->getAttribute($entityTypeId, $code, 'attribute_id');

        //// 3rd: copy data:
        $installer->run("
                INSERT INTO catalog_product_entity_{$type} 
                (entity_type_id, attribute_id, store_id, entity_id, `value`) 
                SELECT {$entityTypeId}, {$attrId}, 0, entity_id, {$tmpCode} FROM {$entityTableName} WHERE {$tmpCode} IS NOT NULL
        ");

        //// 4th: drop static attr & column
        $installer->removeAttribute('catalog_product',$tmpCode);
        $installer->run("ALTER TABLE {$entityTableName} DROP {$tmpCode}");
    }
}

// drop unneccesary dynamic:
foreach ( $dynamicAttrsToRemove as $killMe ) {
    $oldAttr = $code2attrMap[$killMe];
    $id = $oldAttr->getAttributeId();
    $type = $oldAttr->getBackendType();
    $installer->removeAttribute('catalog_product',$killMe);
    $installer->run("DELETE FROM catalog_product_entity_{$type} WHERE attribute_id = {$id} AND entity_type_id = {$entityTypeId}");
    $installer->run("DELETE FROM eav_attribute_option_value WHERE option_id IN (SELECT option_id FROM eav_attribute_option WHERE attribute_id = {$id});");
    $installer->run("DELETE FROM eav_attribute_option WHERE attribute_id = {$id};");
}

// drop unneccesary static attrs:
foreach ( $staticAttrsToRemove as $killMe ) {
    $installer->removeAttribute('catalog_product',$killMe);
    $installer->run("ALTER TABLE {$entityTableName} DROP `{$killMe}`");
}

// get all remaining statics:
$colNames = array();
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$readresult = $write->query("SHOW COLUMNS FROM {$entityTableName}");
while ( $row = $readresult->fetch() ) {
    $colNames[] = $row['Field'];
}

// rename statics: 
foreach ( $staticAttrsToRenameOld2new as $oldName => $newName ) {
    if ( in_array($newName,$colNames) ) {
        $installer->run("ALTER TABLE {$entityTableName} DROP `{$newName}`");
        array_splice($colNames, array_search($newName, $colNames), 1);
    }
    $installer->updateAttribute('catalog_product', $oldName, array('attribute_code' => $newName));
    $type = $staticAttrSqlTypes[$code];
    $installer->run("ALTER TABLE {$entityTableName} CHANGE {$oldName} {$newName} {$type}");
}

// drop remaining columns without attr definition:
foreach ( $colNames as $colName ) {
    if ( ! in_array($colName,$staticAttrsToStay) ) {
        $installer->run("ALTER TABLE {$entityTableName} DROP `{$colName}`");
    }
}

// expand positions to INT(19) because we will get int64
$installer->run("ALTER TABLE catalog_category_product CHANGE COLUMN position position INT(19) NOT NULL DEFAULT '0';");
$installer->run("ALTER TABLE catalog_category_entity CHANGE COLUMN position position INT(19) NOT NULL;");

// delete all product-category relations
$installer->run("DELETE FROM catalog_category_product;");

// delete all rewrites
$installer->run("DELETE FROM core_url_rewrite");

$installer->endSetup();

?>