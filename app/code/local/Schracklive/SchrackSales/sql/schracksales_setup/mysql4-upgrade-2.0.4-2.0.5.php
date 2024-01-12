<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$installer->run("
    ALTER TABLE sales_flat_order_schrack_index ADD wws_followup_order_number VARCHAR(9) AFTER credit_memo_id;
");
$installer->endSetup();

/* make sure we have the same setup as all other order fields */

?>
