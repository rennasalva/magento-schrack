<?php

$installer = $this;
$installer->startSetup();
$installer->run("

	DELETE FROM `acl_roles_resources` where `id` = 2;
	DELETE FROM `acl_roles` where `id` = 2;
	DELETE FROM `acl_resources` where `id` = 1;

	INSERT INTO `acl_roles` (`id`, `name`, `parent_id`) VALUES (4, 'price', 5);
	INSERT INTO `acl_roles` (`id`, `name`, `parent_id`) VALUES (5, 'order', 6);
	INSERT INTO `acl_roles` (`id`, `name`, `parent_id`) VALUES (6, 'orderadmin', 7);
	INSERT INTO `acl_roles` (`id`, `name`, `parent_id`) VALUES (7, 'accountorderadmin', 3);


	INSERT INTO `acl_resources` (`id`, `name`, `parent_id`) VALUES (3, 'seeprices', 4);
	INSERT INTO `acl_resources` (`id`, `name`, `parent_id`) VALUES (4, 'allowedtoorder', 5);
	INSERT INTO `acl_resources` (`id`, `name`, `parent_id`) VALUES (5, 'orderadministration', 6);
	INSERT INTO `acl_resources` (`id`, `name`, `parent_id`) VALUES (6, 'accountorderadministration', 2);


	INSERT INTO `acl_roles_resources` (`id`, `acl_role_id`, `acl_resource_id`, `privilege`) VALUES (4, 4, 3, '*');
	INSERT INTO `acl_roles_resources` (`id`, `acl_role_id`, `acl_resource_id`, `privilege`) VALUES (5, 5, 4, '*');
	INSERT INTO `acl_roles_resources` (`id`, `acl_role_id`, `acl_resource_id`, `privilege`) VALUES (6, 6, 5, '*');
	INSERT INTO `acl_roles_resources` (`id`, `acl_role_id`, `acl_resource_id`, `privilege`) VALUES (7, 7, 6, '*');

");
$installer->endSetup();
