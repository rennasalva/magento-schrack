<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `customer_dsgvo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NULL DEFAULT NULL,
  `schrack_confirmed_dsgvo` tinyint(1) NULL DEFAULT NULL,
  `schrack_confirmed_dsgvo_confirm_text` varchar(2048) NULL DEFAULT NULL,
  `schrack_confirmed_dsgvo_confirm_checkboxtext` varchar(255) NULL DEFAULT NULL,
  `schrack_confirmed_agb` tinyint(1) NULL DEFAULT NULL,
  `schrack_confirmed_agb_confirm_checkboxtext` varchar(255) NULL DEFAULT NULL,
  `schrack_confirmed_dataprotection` tinyint(1) NULL DEFAULT NULL,
  `schrack_confirmed_dataprotection_confirm_checkboxtext` varchar(255) NULL DEFAULT NULL,
  `schrack_confirmed_rightsinformation_date` datetime NULL DEFAULT NULL,
  `schrack_confirmed_rightsinformation_notice` varchar(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `customer_entity` ADD COLUMN `schrack_confirmed_dsgvo` tinyint(1) NULL DEFAULT NULL AFTER `schrack_default_payment_pickup`;
");

$installer->endSetup();