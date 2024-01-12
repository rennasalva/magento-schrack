<?php
$installer = $this;

$installer->startSetup();
$installer->run("ALTER TABLE sales_flat_quote_item ADD schrack_detailview_drum_number VARCHAR(64) NULL DEFAULT NULL;");
$installer->endSetup();

?>