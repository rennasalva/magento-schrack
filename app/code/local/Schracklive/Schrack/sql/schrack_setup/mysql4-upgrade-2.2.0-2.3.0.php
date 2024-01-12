<?php

$installer = $this;
$installer->startSetup();
$installer->run("
    CREATE TABLE `msg_modification_timestamps` (
        `msg_key` VARCHAR(128) NOT NULL,
        `last_modified_ts` VARCHAR(24) NOT NULL,
        PRIMARY KEY (`msg_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup();
