<?php
$installer = $this;

$installer->startSetup();
$installer->run("ALTER TABLE `sales_flat_quote_item` ADD COLUMN `schrack_item_description` VARCHAR(120) NULL DEFAULT NULL;");
$installer->endSetup();

?>