<?php

$installer = $this;
$installer->startSetup();
$installer->run("

	INSERT INTO `acl_roles_resources` (`acl_role_id`, `acl_resource_id`, `privilege`) VALUES (10, 4, 'view,order');

	UPDATE `acl_roles` SET `is_visible`=1 WHERE `id`=3;
	UPDATE `acl_roles` SET `is_visible`=1 WHERE `id`=4;
	UPDATE `acl_roles` SET `is_visible`=1 WHERE `id`=5;

");
$installer->endSetup();
