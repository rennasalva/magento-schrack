<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("

CREATE TABLE schrack_ids_data (
  `wws_customer_id` varchar(12) NULL,
  `email` varchar(64) NOT NULL, 
  `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `current_action` varchar(12) NULL,
  `hookurl` varchar(128) NULL,
  `external_ordernumber` varchar(128) NULL,
  `external_version` varchar(5) NULL,
  `cart_normal` mediumtext NULL, 
  `cart_extra` mediumtext NULL,
  `ids_wks_positions` mediumtext NULL,
  `selected_payment` varchar(64) NULL,
  `selected_shipping` varchar(64) NULL,
  `delivery_address` mediumtext NULL,  
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL,    
  PRIMARY KEY (`created_at`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `core_config_data` (`scope`, `scope_id`, `path`, `value`) VALUES ('default', 0, 'ids/active/state', '0');
INSERT INTO `core_config_data` (`scope`, `scope_id`, `path`, `value`) VALUES ('default', 0, 'ids/always_active_for_testing/email', '');

");

$installer->endSetup();
