<?php

$installer = $this;
$installer->startSetup();
$installer->run("
    CREATE TABLE `tokens` (
        `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `token` VARCHAR(128) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `user_id` INT(10) UNSIGNED DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup();
