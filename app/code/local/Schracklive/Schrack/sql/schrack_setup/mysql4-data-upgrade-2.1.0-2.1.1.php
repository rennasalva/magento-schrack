<?php

$installer = $this;
$installer->startSetup();
$installer->run("

	UPDATE `acl_roles_resources` SET `privilege`='view,order' WHERE `acl_role_id`=10 AND `acl_resource_id`=3;
	INSERT INTO `acl_roles_resources` (`acl_role_id`, `acl_resource_id`, `privilege`) VALUES (10, 5, 'view,order');

");
$installer->endSetup();