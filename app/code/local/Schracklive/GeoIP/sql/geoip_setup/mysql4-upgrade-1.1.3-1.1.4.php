<?php

$installer = $this;

$installer->startSetup();

$installer->run("
    CREATE TABLE `maxmind_geoip_log` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `ip` VARCHAR(128) NOT NULL, 
      `country` CHAR(4) NOT NULL,
      `created_at` DATETIME NOT NULL,
      `updated_at` DATETIME NOT NULL,
      primary key (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    INSERT INTO `core_config_data` (`scope`, `scope_id`, `path`, `value`, `updated_at`) VALUES ('default', '0', 'maxmind/geoip/account/id', '813026', now());
    INSERT INTO `core_config_data` (`scope`, `scope_id`, `path`, `value`, `updated_at`) VALUES ('default', '0', 'maxmind/geoip/license/key', 'tReyMI87XFvoMv93', now());
    INSERT INTO `core_config_data` (`scope`, `scope_id`, `path`, `value`, `updated_at`) VALUES ('default', '0', 'maxmind/geoip/url', 'https://geoip.maxmind.com/geoip/v2.1/country/', now());
");

$installer->endSetup();