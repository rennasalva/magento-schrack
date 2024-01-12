<?php

$installer = $this;
$installer->startSetup();
$installer->run("

	ALTER TABLE `acl_roles` ADD COLUMN `is_visible` TINYINT(1) NOT NULL DEFAULT '0';

");
$installer->endSetup();
