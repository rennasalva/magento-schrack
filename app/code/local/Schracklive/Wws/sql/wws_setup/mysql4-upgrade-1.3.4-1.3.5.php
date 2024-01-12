<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("        
    ALTER TABLE sales_flat_quote_item ADD COLUMN schrack_wws_cuttingfee decimal(12,4) NULL AFTER schrack_surcharge;
");

$installer->endSetup();
