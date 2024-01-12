<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

CREATE TABLE `partslist_sharing_map` (
`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
`active` tinyint UNSIGNED NOT NULL DEFAULT 0,
`shared_partslist_id` int(10) NOT NULL,
`schrack_wws_customer_id`  varchar(6) NOT NULL,
`schrack_wws_contact_number_sharer` int(11) NOT NULL,
`email_sharer` varchar(255) NOT NULL,
`firstname_sharer` varchar(255) NOT NULL,
`lastname_sharer` varchar(255) NOT NULL,
`schrack_wws_contact_number_receiver` int(11) NOT NULL,
`email_receiver` varchar(255) NOT NULL,
`firstname_receiver` varchar(255) NOT NULL,
`lastname_receiver` varchar(255) NOT NULL,
`last_update_notification_at` datetime NOT NULL,
`last_update_notification_flag` tinyint UNSIGNED NOT NULL DEFAULT 1,
`last_update_notification_received` tinyint UNSIGNED NOT NULL DEFAULT 0,
`created_at` datetime NOT NULL,
`updated_at` datetime NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();