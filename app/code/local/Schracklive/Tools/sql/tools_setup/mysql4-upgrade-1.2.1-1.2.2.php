<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data SET `path` = 'schrack/customertools/enable_distribution_board_configurator', `value`= '0';
    INSERT INTO core_config_data SET `path` = 'schrack/customertools/distribution_board_configurator_url', `value` = 'https://portal.combeenation.com/Cfgr/Schrack/VERTEILER';
");

$installer->endSetup();
