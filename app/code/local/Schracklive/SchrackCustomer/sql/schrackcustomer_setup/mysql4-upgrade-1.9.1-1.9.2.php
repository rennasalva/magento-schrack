<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

$search  = '{{var lastname}}{{/if}}{{var street}}';
$replace = '{{var lastname}}, {{/if}}{{var street}}';

$sql = "SELECT path, value FROM core_config_data WHERE path LIKE 'customer/address_templates/%';";
$rows = $readConnection->fetchAll($sql);
foreach ( $rows as $row ) {
    $path = $row['path'];
    $val = $row['value'];
    $newVal = str_replace($search,$replace,$val);
    if ( $newVal != $val ) {
        $sql = "UPDATE core_config_data SET value = ? WHERE path = ?;";
        $writeConnection->query($sql,array($newVal,$path));
    }
}

$installer->endSetup();
