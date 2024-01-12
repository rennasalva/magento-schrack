<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$tableNameOrder              = $installer->getTable('sales/order');

$installer->run("
    ALTER TABLE {$tableNameOrder}   ADD schrack_wws_operator_mail VARCHAR(128) DEFAULT NULL AFTER schrack_wws_ship_memo;
");

$installer->addAttribute('order','schrack_wws_operator_mail',array('type'=>'static','required'=>false,'label'=>'Operator Mail'));

$installer->endSetup();


?>
