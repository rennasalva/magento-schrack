<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$tableNameOrder              = $installer->getTable('sales/order');

$installer->run("
    ALTER TABLE {$tableNameOrder} ADD schrack_customer_delivery_info VARCHAR(255) AFTER schrack_customer_project_info;
");

$installer->addAttribute('order','schrack_customer_delivery_info',array('type'=>'static','required'=>false,'label'=>'Customer Delivery Info'));

$installer->endSetup();


?>
