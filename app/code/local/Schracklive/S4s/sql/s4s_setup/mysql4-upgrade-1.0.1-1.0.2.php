<?php

$installer = $this;
$installer->startSetup();
$installer->run("
    ALTER TABLE `customer_entity` CHANGE `schrack_s4s_nickname` `schrack_s4s_nickname` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin;
");
$installer->endSetup();
