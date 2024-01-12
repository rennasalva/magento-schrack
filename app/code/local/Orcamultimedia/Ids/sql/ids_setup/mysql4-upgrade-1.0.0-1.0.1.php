<?php

$installer = $this;
$installer->startSetup();
$installer->run("
	ALTER TABLE `schrack_ids_data` ADD COLUMN `wws_order_number` varchar(15) NULL;
");
$installer->endSetup();
