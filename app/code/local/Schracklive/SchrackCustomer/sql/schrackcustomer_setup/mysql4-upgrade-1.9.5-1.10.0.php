<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("

CREATE TABLE schrack_terms_of_use (
  `entity_id` int(10) unsigned NOT NULL auto_increment,
  `version` varchar(64) NOT NULL,  
  `content` mediumtext NULL,  
  `content_hash` varchar(128) NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`entity_id`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE schrack_terms_of_use_confirmation (
  `user_email` varchar(255) NOT NULL,  
  `terms_id` int(10) unsigned NOT NULL,
  `terms_version` varchar(64) NOT NULL,
  `client_terms_content_hash` varchar(128) NOT NULL,  
  `client_ip` varchar(64) default NULL,  
  `client_ip_remote` varchar(64) default NULL,  
  `client_type` varchar(64) NOT NULL,
  `customer_id` int(10) unsigned default NULL,
  `confirmed_at` timestamp NOT NULL,
  PRIMARY KEY (`user_email`, `terms_id`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `customer_entity` ADD COLUMN `schrack_last_terms_confirmed` tinyint(1) NOT NULL DEFAULT 0 AFTER `schrack_changepw_token`;

");

$installer->addAttribute('customer', 'schrack_last_terms_confirmed', ['type' => 'static', 'required' => true, 'label' => 'Terms of Use confirmed']);

$installer->endSetup();
