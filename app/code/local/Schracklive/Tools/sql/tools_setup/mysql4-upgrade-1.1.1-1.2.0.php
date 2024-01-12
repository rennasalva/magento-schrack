<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("    
    CREATE TABLE schrack_adddress_validation (
      `id` bigint unsigned NOT NULL AUTO_INCREMENT,      
      `address` varchar(255) NOT NULL,
      `country` varchar(3) NOT NULL,
      `credit` DECIMAL(3,1) NOT NULL,
      `current_month` varchar(12) NOT NULL,
      `result` varchar(12) NOT NULL,
      `created_at` datetime NOT NULL,
      PRIMARY KEY (`id`)      
    ) ENGINE=INNODB DEFAULT CHARSET=UTF8;
");

$installer->endSetup();
