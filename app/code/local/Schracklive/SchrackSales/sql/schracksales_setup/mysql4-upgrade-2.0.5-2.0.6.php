<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$installer->run("
     alter table sales_flat_order add index `faster_login_2` (schrack_wws_status, entity_id);
");
$installer->endSetup();

?>
