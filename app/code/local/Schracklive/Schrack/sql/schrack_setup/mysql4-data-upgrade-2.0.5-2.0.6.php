<?php

$installer = $this;
$installer->startSetup();
$installer->run("

	UPDATE `acl_roles` SET `name`='staff' WHERE `id`=4;
	UPDATE `acl_roles` SET `name`='customer' WHERE `id`=5;
	UPDATE `acl_roles` SET `name`='manager' WHERE `id`=6;
	UPDATE `acl_roles` SET `name`='affiliate' WHERE `id`=7;

	UPDATE `acl_resources` SET `name`='accessRight' WHERE `id`=2;
	UPDATE `acl_resources` SET `name`='affiliateOrder' WHERE `id`=6;
	UPDATE `acl_resources` SET `name`='price' WHERE `id`=3;
	UPDATE `acl_resources` SET `name`='customerOrder' WHERE `id`=4;
	UPDATE `acl_resources` SET `name`='accountOrder' WHERE `id`=5;

	UPDATE `acl_roles_resources` SET `privilege`='view,edit' WHERE `id`=3;
	UPDATE `acl_roles_resources` SET `privilege`='view' WHERE `id`=4;
	UPDATE `acl_roles_resources` SET `privilege`='view,order' WHERE `id`=5;
	UPDATE `acl_roles_resources` SET `privilege`='view,order' WHERE `id`=6;
	UPDATE `acl_roles_resources` SET `privilege`='view,order' WHERE `id`=7;



");
$installer->endSetup();
