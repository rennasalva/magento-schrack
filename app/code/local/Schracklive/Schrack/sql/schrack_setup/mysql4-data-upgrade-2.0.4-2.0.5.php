<?php

$installer = $this;
$installer->startSetup();
$installer->run("

	UPDATE `acl_roles` SET `parent_id`=7 WHERE `id`=3;
	UPDATE `acl_roles` SET `parent_id`=0 WHERE `id`=4;
	UPDATE `acl_roles` SET `parent_id`=4 WHERE `id`=5;
	UPDATE `acl_roles` SET `parent_id`=5 WHERE `id`=6;
	UPDATE `acl_roles` SET `parent_id`=6 WHERE `id`=7;



");
$installer->endSetup();
