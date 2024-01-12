<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

CREATE TABLE `national_holidays_import_data` (
`id` int UNSIGNED NOT NULL,
`json_data` longtext NOT NULL,
`json_data_import_datetime` datetime NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `national_holidays` (
`id` int UNSIGNED NOT NULL,
`holiday_datetime` datetime NOT NULL,
`import_datetime` datetime NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();