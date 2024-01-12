<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

	ALTER TABLE `acl_roles` ADD `position` tinyint(2) NULL;
	UPDATE `acl_roles` SET `position`=1 WHERE `id`=3;
	UPDATE `acl_roles` SET `position`=2 WHERE `id`=5;
	UPDATE `acl_roles` SET `position`=3 WHERE `id`=4;

");

$installer->endSetup();
