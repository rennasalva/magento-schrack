<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$tableNameOrder              = $installer->getTable('sales/order');

$installer->run("
    ALTER TABLE {$tableNameOrder}      ADD schrack_is_orderable           INT(1) DEFAULT 1 NOT NULL AFTER schrack_is_complete;
");
$installer->endSetup();

/* make sure we have the same setup as all other order fields */
$installer->addAttribute('order', 'schrack_is_orderable', array('type'=>'static','required'=>false,'label'=>'Is Complete'));


?>
