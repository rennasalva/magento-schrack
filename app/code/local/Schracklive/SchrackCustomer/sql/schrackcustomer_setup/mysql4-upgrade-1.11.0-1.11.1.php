<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE schrack_terms_of_use MODIFY COLUMN created_at datetime NOT NULL AFTER content_hash_without_html_tags;
");

$installer->endSetup();
