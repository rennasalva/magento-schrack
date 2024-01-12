<?php

$installer = $this;
$installer->startSetup();

// Init datetime:
$created_at = date('Y-m-d H:i:s');

// Create URL for Menu Creation Webservice:
$readConnection  = Mage::getSingleton('core/resource')->getConnection('core_read');
$query = "SELECT value FROM core_config_data WHERE path LIKE 'web/secure/base_url' AND scope LIKE 'stores'";
$baseURL = $readConnection->fetchOne($query);
$completeMenuWebServiceUrl = $baseURL . 'skin/frontend/schrack/Public/restapi/restapi.php';

// URL for fetching JSON to build menu:
$typoBaseUrl = Mage::getStoreConfig('schrack/typo3/typo3url');
$fetchMenuPartialFromTypo = $typoBaseUrl . 'contentEID=export_pages&type=all';

$installer->run("
    CREATE TABLE IF NOT EXISTS shop_navigation (
	`id` int(11) unsigned NOT NULL auto_increment,
	`type` varchar(32) NOT NULL,
	`source` varchar(32) NOT NULL,
	`content` longtext NOT NULL,
	`active` tinyint(3) NULL,
	`created_at` datetime NOT NULL,
	PRIMARY KEY (id),
	UNIQUE INDEX `ux_id` (`id`)	
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    INSERT INTO shop_navigation SET `type` = 'top-nav', `source` = 'shop', `content` = 'top_nav_products', `active` = 1, `created_at` = '{$created_at}';    
    INSERT INTO shop_navigation SET `type` = 'top-nav', `source` = 'typo', `content` = 'top_nav_1_typo', `active` = 1, `created_at` = '{$created_at}';    
    INSERT INTO shop_navigation SET `type` = 'top-nav', `source` = 'typo', `content` = 'top_nav_2_typo', `active` = 1, `created_at` = '{$created_at}';    
    INSERT INTO shop_navigation SET `type` = 'top-nav', `source` = 'typo', `content` = 'top_nav_3_typo', `active` = 1, `created_at` = '{$created_at}';    
    INSERT INTO shop_navigation SET `type` = 'top-nav', `source` = 'typo', `content` = 'top_nav_4_typo', `active` = 1, `created_at` = '{$created_at}';
    
    INSERT INTO core_config_data SET `path` = 'schrack/typo3/typo3_fetch_menu_partial_url', `value` = '{$fetchMenuPartialFromTypo}';
    
    INSERT INTO core_config_data SET `path` = 'schrack/general/generatemenuserviceurl', `value` = '{$completeMenuWebServiceUrl}';
");

$installer->endSetup();