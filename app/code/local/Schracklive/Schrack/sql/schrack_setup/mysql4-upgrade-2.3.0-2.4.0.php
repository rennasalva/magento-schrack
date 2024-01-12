<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

	INSERT INTO `acl_roles` SET
	`id` = 11,
	`name` = 'projectant',
	`parent_id` = 0,
	`is_visible` = 1,
	`position` = 4;

");

$installer->endSetup();