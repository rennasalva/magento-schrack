<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS synonyms;
CREATE TABLE synonyms (
  `term` varchar(255) NOT NULL,
  `synonyms` text,
  PRIMARY KEY  (`term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");


$installer->endSetup();

