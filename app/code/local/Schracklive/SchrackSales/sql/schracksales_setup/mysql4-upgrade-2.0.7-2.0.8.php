<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$tableNameOrder              = $installer->getTable('sales/order');

$installer->run("
    ALTER TABLE {$tableNameOrder}   ADD schrack_wws_offer_valid_thru DATETIME DEFAULT NULL AFTER schrack_wws_offer_date;
    ALTER TABLE {$tableNameOrder}   ADD schrack_wws_offer_flag_valid INT(1) DEFAULT 0 NOT NULL AFTER schrack_wws_offer_valid_thru;
    ALTER TABLE {$tableNameOrder}   ADD schrack_wws_web_send_no VARCHAR(20) DEFAULT NULL AFTER schrack_wws_offer_flag_valid;
");

$installer->addAttribute('order', 'schrack_wws_offer_valid_thru', array('type'=>'static','required'=>false,'label'=>'Offer valid thru'));
$installer->addAttribute('order', 'schrack_wws_offer_flag_valid', array('type'=>'static','required'=>false,'label'=>'Offer is valid'));
$installer->addAttribute('order', 'schrack_wws_web_send_no',      array('type'=>'static','required'=>false,'label'=>'Web send number'));

$installer->endSetup();

/* make sure we have the same setup as all other order fields */

?>
