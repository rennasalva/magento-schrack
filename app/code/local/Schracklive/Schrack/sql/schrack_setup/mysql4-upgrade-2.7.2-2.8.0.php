<?php

$installer = $this;
$installer->startSetup();

$installer->run("    
     CREATE TABLE IF NOT EXISTS schrack_poll (	 
	 `schrack_poll_id` tinyint(3) NOT NULL,
	 `size` tinyint(3) NOT NULL DEFAULT 0,
	 `active` tinyint(3) NOT NULL DEFAULT 0,
	 `html` longtext NULL NULL,
	 `created_at` datetime NOT NULL,
	 PRIMARY KEY (schrack_poll_id),
	 UNIQUE INDEX `ux_id` (`schrack_poll_id`)	
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8;  
     
     CREATE TABLE IF NOT EXISTS schrack_poll_config (
	 `id` int(10) unsigned NOT NULL,
	 `schrack_poll_id` tinyint(3) NOT NULL,	
	 `sts_key` varchar(128) NOT NULL,	 
	 `answer_type` varchar(128) NOT NULL,	
	 `category` varchar(128) NOT NULL,	
	 `position` int(10) unsigned NOT NULL,
	 `active` tinyint(3) NOT NULL DEFAULT 1,
	 `created_at` datetime NOT NULL,
	 PRIMARY KEY (id),
	 UNIQUE INDEX `ux_id` (`id`)	
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8;  
   
     CREATE TABLE IF NOT EXISTS schrack_poll_tracking (
	 `id` bigint(20) unsigned NOT NULL auto_increment,
	 `email` varchar(128) NOT NULL,
	 `schrack_poll_id` tinyint(3) NOT NULL,	 
	 `status` varchar(20) NULL,
	 `cycle` int(10) NULL DEFAULT 0,
	 `counter` int(10) NULL,
	 `created_at` datetime NOT NULL,
	 `updated_at` datetime NOT NULL,
	 PRIMARY KEY (id),
	 UNIQUE INDEX `ux_id` (`id`)	
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8;  
   
     CREATE TABLE IF NOT EXISTS schrack_poll_result (
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`email` varchar(128) NOT NULL,
	`schrack_poll_id` tinyint(3) NOT NULL,	
	`position` int(10) NOT NULL,	
	`answer` mediumtext NULL,
	`answer_translated` mediumtext NULL,
	`answer_type` varchar(128) NOT NULL,	
	`created_at` datetime NOT NULL,
	PRIMARY KEY (id),
	UNIQUE INDEX `ux_id` (`id`)	
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;  
    
    INSERT INTO core_config_data SET `path` = 'schrack/shop/schrack_poll_active', `value` = '';
");

$installer->endSetup();
