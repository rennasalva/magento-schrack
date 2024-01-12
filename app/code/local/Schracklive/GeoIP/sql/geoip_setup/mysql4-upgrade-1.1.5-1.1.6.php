<?php

$installer = $this;

$installer->startSetup();

$installer->run("

    DELETE FROM `core_config_data` WHERE `path` = 'schrack/general/loggedIn/noPrices';
");

$installer->endSetup();