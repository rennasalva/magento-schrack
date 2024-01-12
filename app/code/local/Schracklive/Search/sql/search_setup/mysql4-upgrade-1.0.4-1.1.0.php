<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    CREATE TABLE schrack_selected_search_result_articles (
        `id` 			    int(10) unsigned	NOT NULL AUTO_INCREMENT,
        `query` 			varchar(256) 		NOT NULL,
        `selected_sku` 		varchar(16) 		NOT NULL,
        `selected_at` 		timestamp			NOT NULL DEFAULT NOW(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");

$installer->endSetup();
