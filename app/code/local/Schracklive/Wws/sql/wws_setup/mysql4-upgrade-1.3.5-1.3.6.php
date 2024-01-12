<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('wws_insert_update_order_request')};
CREATE TABLE {$this->getTable('wws_insert_update_order_request')} (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `unique_log_id` varchar(255) NULL,
  `wws_order_id` varchar(9) NULL,
  `pickup_method` tinyint(1) NULL,
  `payment_method` tinyint(1) NULL,
  `payment_method_definition` varchar(55) NULL,
  `payment_method_definition_german` varchar(55) NULL,
  `user_email` varchar(255) NULL,
  `wws_customer_id` varchar(255) NULL,
  `wws_contact_number` int UNSIGNED NULL,
  `request_datetime` datetime NULL,
  `response_fetched_successfully` tinyint(1) NULL,
  PRIMARY KEY (`id`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- DROP TABLE IF EXISTS {$this->getTable('wws_insert_update_order_response')};
CREATE TABLE {$this->getTable('wws_insert_update_order_response')} (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `unique_log_id` varchar(255) NULL,
  `wws_order_id` varchar(9) NULL,
  `user_email` varchar(255) NULL,
  `wws_customer_id` varchar(255) NULL,
  `amount_net` decimal(12,4) NULL,
  `amount_tax` decimal(12,4) NULL,
  `amount_tot` decimal(12,4) NULL,
  `base_currency` varchar(10) NULL,
  `response_datetime` datetime NULL,
  `exit_code` smallint(5) UNSIGNED NULL,
  `exit_message` varchar(255) NULL,
  `memo_string` varchar(1024) NULL,
  `has_discount` tinyint(1) NULL,
  PRIMARY KEY (`id`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- DROP TABLE IF EXISTS {$this->getTable('wws_ship_order_request')};
CREATE TABLE {$this->getTable('wws_ship_order_request')} (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,  
  `wws_order_id` varchar(9) NULL,    
  `user_email` varchar(255) NULL,
  `wws_customer_id` varchar(255) NULL,
  `flag_order` int NULL,
  `request_datetime` datetime NULL,
  PRIMARY KEY (`id`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup();