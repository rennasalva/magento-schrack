<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$sql = " INSERT INTO core_config_data (scope,scope_id,path,value) VALUES('default',0,?,?)"
     . " ON DUPLICATE KEY UPDATE value=?";
$writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
$writeConnection->beginTransaction();
try {
    $path = 'schrack/s4s/advisor_principal_name';
    if ( ! Mage::getStoreConfig($path) ) {
        $dfltVal = Mage::getStoreConfig('schrack/shop/default_advisor');
        $writeConnection->query($sql, [$path, $dfltVal, $dfltVal]);
    }
    $path = 'schrack/s4s/branch'; $dfltVal = '11'; // online sales
    if ( ! Mage::getStoreConfig($path) ) {
        $writeConnection->query($sql, [$path, $dfltVal, $dfltVal]);
    }
    $path = 'schrack/s4s/salesarea'; $dfltVal = '11'; // online sales
    if ( ! Mage::getStoreConfig($path) ) {
        $writeConnection->query($sql, [$path, $dfltVal, $dfltVal]);
    }
    $writeConnection->commit();
} catch ( Exception $ex ) {
    $writeConnection->rollback();
    throw $ex;
}

$installer->endSetup();