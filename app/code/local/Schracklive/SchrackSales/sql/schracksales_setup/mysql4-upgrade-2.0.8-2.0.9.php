<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$installer->run("
     alter table sales_flat_order_schrack_index add index `wws_customer_id` (wws_customer_id);
");
$installer->endSetup();

?>
