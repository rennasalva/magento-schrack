<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$tableNameOrder              = $installer->getTable('sales/order');

$installer->run("
    ALTER TABLE {$tableNameOrder} ADD schrack_is_current_downloaded INT(1) NOT NULL DEFAULT 0 AFTER schrack_wws_ship_memo;
");

$installer->addAttribute('order','schrack_is_current_downloaded',array('type'=>'static','required'=>false,'label'=>'Current Version Is Already Downloaded'));

$installer->endSetup();


?>
