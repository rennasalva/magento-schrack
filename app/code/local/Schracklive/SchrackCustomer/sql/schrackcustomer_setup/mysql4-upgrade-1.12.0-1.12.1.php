<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("

CREATE TABLE schrack_vat_softcheck_cache (    
  `vat` varchar(64) NOT NULL,  
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`vat`),
  KEY `IDX_SCHRACK_VAT` (`vat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();
