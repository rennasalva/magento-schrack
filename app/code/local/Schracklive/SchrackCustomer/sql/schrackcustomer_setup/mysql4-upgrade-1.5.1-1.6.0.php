<?php //

/*
$installer = $this;
// @var $installer Mage_Core_Model_Resource_Setup

$installer->startSetup();

$installer->run("
INSERT INTO `customer_group` SET customer_group_id = 11, customer_group_code ='Schrack Interessent', `tax_class_id` = 3;
INSERT INTO `customer_group` SET customer_group_id = 12, customer_group_code ='Schrack Interessent Lite', `tax_class_id` = 3;
ALTER TABLE `sales_flat_quote` ADD schrack_customertype VARCHAR(20);
    ");

$installer->endSetup();
*/
//$installer->addAttribute('quote', 'schrack_customertype', array('type'=>'static','required'=>false,'label'=>'Customer Registration Type'));