<?php

$installer = $this;
$installer->startSetup();

$installer->run("
     CREATE TABLE IF NOT EXISTS schrack_message_bars (
	`uid` int(11) unsigned NOT NULL,
	`accountType` tinyint(3) NOT NULL,
	`body` mediumtext NOT NULL,
	`branchId` int(11) unsigned NULL,
	`campaignName` varchar (255) NOT NULL,
	`loginState` tinyint(3) NOT NULL,
	`pid` int(11) unsigned NOT NULL,
	`type` tinyint(3) NULL,
	`link` varchar (255) NULL,
	`linkText` varchar (255) NULL,
	`active` tinyint(3) NOT NULL DEFAULT 0,
	`created_at` datetime NOT NULL,
	PRIMARY KEY (uid),
	UNIQUE INDEX `ux_uid` (`uid`)	
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    INSERT INTO core_config_data SET `path` = 'schrack/typo3/message_bars_logging', `value` = '';
    INSERT INTO core_config_data SET `path` = 'schrack/typo3/message_bars_fetch_active', `value` = '';
    INSERT INTO core_config_data SET `path` = 'schrack/typo3/message_bars_testing', `value` = '';
    INSERT INTO core_config_data SET `path` = 'schrack/typo3/message_bars_service_url', `value` = '';   
");

$installer->endSetup();
