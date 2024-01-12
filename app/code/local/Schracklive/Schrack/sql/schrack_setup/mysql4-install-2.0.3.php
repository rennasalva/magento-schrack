<?php

$installer = $this;
$installer->startSetup();
$installer->run("
    CREATE TABLE `acl_roles` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(45) NULL DEFAULT NULL,
	`parent_id` INT(10) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	CREATE TABLE `acl_resources` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(45) NULL DEFAULT NULL,
	`parent_id` INT(10) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	CREATE TABLE `acl_roles_resources` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`acl_role_id` INT(10) UNSIGNED NOT NULL,
	`acl_resource_id` INT(10) UNSIGNED NOT NULL,
	`privilege` VARCHAR(45) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `ux_role_res_priv` (`acl_role_id`, `acl_resource_id`, `privilege`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	INSERT INTO `acl_roles` (`id`, `name`, `parent_id`) VALUES (2, 'staff', 0);
	INSERT INTO `acl_roles` (`id`, `name`, `parent_id`) VALUES (3, 'admin', 0);

	INSERT INTO `acl_resources` (`id`, `name`, `parent_id`) VALUES (1, 'price', 0);
	INSERT INTO `acl_resources` (`id`, `name`, `parent_id`) VALUES (2, 'customeradministration', 0);

	INSERT INTO `acl_roles_resources` (`id`, `acl_role_id`, `acl_resource_id`, `privilege`) VALUES (2, 2, 1, '*');
	INSERT INTO `acl_roles_resources` (`id`, `acl_role_id`, `acl_resource_id`, `privilege`) VALUES (3, 3, 2, '*');


");
$installer->endSetup();
