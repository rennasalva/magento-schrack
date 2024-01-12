<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE `schrack_terms_of_use` ADD COLUMN `content_without_html_tags` mediumtext NULL AFTER `content`;
ALTER TABLE `schrack_terms_of_use` ADD COLUMN `content_hash_without_html_tags` varchar(128) NOT NULL  AFTER `content_hash`;
ALTER TABLE `schrack_terms_of_use` ADD COLUMN `raw_content_changed` tinyint(1) NULL DEFAULT NULL AFTER `created_at`;
");

$installer->endSetup();
