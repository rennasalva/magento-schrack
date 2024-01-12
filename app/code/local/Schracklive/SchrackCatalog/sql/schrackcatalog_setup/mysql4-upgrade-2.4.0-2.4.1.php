<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$thirdPartyVendors = array("SLV" => true,"MH" => true,"ONE" => true,"ZVK" => true,"EGL" => true,"VTC" => true);

$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
$writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

$sql = "SELECT stock_location FROM cataloginventory_stock WHERE stock_number = 999";
$dbRres = $readConnection->fetchCol($sql);

foreach ( $dbRres as $location ) {
    if ( isset($thirdPartyVendors[$location]) ) {
        $thirdPartyVendors[$location] = false;
    }
}

foreach ( $thirdPartyVendors as $location => $flag ) {
    if ( $flag ) {
        $sql = " INSERT INTO cataloginventory_stock (stock_name,stock_number,stock_location,is_pickup,is_delivery,delivery_hours)"
             . " VALUES(?,?,?,?,?,?)";
        $writeConnection->query($sql,array('3rd Party Stock',999,$location,0,1,336));
    }
}

$installer->endSetup();

