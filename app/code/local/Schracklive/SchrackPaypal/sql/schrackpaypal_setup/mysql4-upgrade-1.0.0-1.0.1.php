<?php

$installer = $this;

// @var $installer Mage_Core_Model_Resource_Setup
$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `paypal_get_payment_status` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) UNSIGNED NULL,
  `customer_email` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `schrack_wws_customer_id` varchar(6) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `schrack_wws_order_id` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `total_invoice_amount` varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `base_currency` varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `schrack_status` varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `paypal_status` varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `paypal_request` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `paypal_request_date` datetime NULL,
  `paypal_response` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `paypal_response_date` datetime NULL,
  `transaction_id` varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ship_order_status` tinyint(3) UNSIGNED NULL,
  `ship_order_datetime` datetime NULL,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  INDEX `IDX_CUSTOMER_ENTITY_EMAIL` (`customer_email`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
