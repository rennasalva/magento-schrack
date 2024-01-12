<?php

$installer = $this;
$installer->startSetup();
$installer->run("

	INSERT INTO `acl_roles` (`id`, `name`, `parent_id`) VALUES (8, 'system contact', 6);
	INSERT INTO `acl_roles` (`id`, `name`, `parent_id`) VALUES (9, 'anonymous', 0);
	INSERT INTO `acl_roles` (`id`, `name`, `parent_id`) VALUES (10, 'employee', 0);

	INSERT INTO `acl_roles_resources` (`acl_role_id`, `acl_resource_id`, `privilege`) VALUES (9, 4, 'order');
	INSERT INTO `acl_roles_resources` (`acl_role_id`, `acl_resource_id`, `privilege`) VALUES (10, 3, 'view');

	UPDATE `acl_roles` SET `parent_id`=6 WHERE `id`=3;

	UPDATE `acl_resources` SET `parent_id`=0 WHERE `id`=3;
	UPDATE `acl_resources` SET `parent_id`=0 WHERE `id`=6;

");
$installer->endSetup();
