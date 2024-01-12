<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$tableNameOrder              = $installer->getTable('sales/order');
$tableNameOrderItem          = $installer->getTable('sales/order_item');

$installer->run("
    ALTER TABLE {$tableNameOrder} ADD schrack_customer_project_info VARCHAR(255) AFTER schrack_wws_reference;
    ALTER TABLE {$tableNameOrderItem} ADD schrack_surcharge_desc VARCHAR(255) AFTER schrack_row_total_surcharge;

    ALTER TABLE sales_flat_order ADD schrack_sp_reference_1 VARCHAR(255) DEFAULT NULL AFTER schrack_customer_project_info;
    ALTER TABLE sales_flat_order ADD schrack_sp_reference_2 VARCHAR(255) DEFAULT NULL AFTER schrack_sp_reference_1;
    ALTER TABLE sales_flat_order ADD schrack_sp_reference_3 VARCHAR(255) DEFAULT NULL AFTER schrack_sp_reference_2;
    ALTER TABLE sales_flat_order ADD schrack_sp_reference_4 VARCHAR(255) DEFAULT NULL AFTER schrack_sp_reference_3;
    ALTER TABLE sales_flat_order ADD schrack_sp_reference_5 VARCHAR(255) DEFAULT NULL AFTER schrack_sp_reference_4;

    ALTER TABLE sales_flat_order_item ADD schrack_sp_reference_1 VARCHAR(255) DEFAULT NULL AFTER schrack_position;
    ALTER TABLE sales_flat_order_item ADD schrack_sp_reference_2 VARCHAR(255) DEFAULT NULL AFTER schrack_sp_reference_1;
    ALTER TABLE sales_flat_order_item ADD schrack_sp_reference_3 VARCHAR(255) DEFAULT NULL AFTER schrack_sp_reference_2;
    ALTER TABLE sales_flat_order_item ADD schrack_sp_reference_4 VARCHAR(255) DEFAULT NULL AFTER schrack_sp_reference_3;
    ALTER TABLE sales_flat_order_item ADD schrack_sp_reference_5 VARCHAR(255) DEFAULT NULL AFTER schrack_sp_reference_4;
");

$installer->addAttribute('order','schrack_customer_project_info',array('type'=>'static','required'=>false,'label'=>'Customer Project Info'));
$installer->addAttribute('order','schrack_sp_reference_1',array('type'=>'static','required'=>false,'label'=>'Solution Provider Reference 1'));
$installer->addAttribute('order','schrack_sp_reference_2',array('type'=>'static','required'=>false,'label'=>'Solution Provider Reference 2'));
$installer->addAttribute('order','schrack_sp_reference_3',array('type'=>'static','required'=>false,'label'=>'Solution Provider Reference 3'));
$installer->addAttribute('order','schrack_sp_reference_4',array('type'=>'static','required'=>false,'label'=>'Solution Provider Reference 4'));
$installer->addAttribute('order','schrack_sp_reference_5',array('type'=>'static','required'=>false,'label'=>'Solution Provider Reference 5'));

$installer->endSetup();


?>
