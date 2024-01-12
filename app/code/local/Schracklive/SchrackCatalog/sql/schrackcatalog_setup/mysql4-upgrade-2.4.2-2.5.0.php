<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE schrack_dictionary (
  `term` varchar(255) NOT NULL, 
  `created_at` datetime NOT NULL, 
  PRIMARY KEY  (`term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");


$installer->endSetup();

