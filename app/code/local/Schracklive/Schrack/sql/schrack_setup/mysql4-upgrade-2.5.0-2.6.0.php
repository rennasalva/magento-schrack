<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

CREATE TABLE `partslist_cart_sharing_csv_counter` (
`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
`sharing_type` varchar(20) NOT NULL,
`customer_entity_id_sharer` int UNSIGNED NOT NULL,
`email_receiver` varchar(255) NOT NULL,
`created_at` datetime NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();