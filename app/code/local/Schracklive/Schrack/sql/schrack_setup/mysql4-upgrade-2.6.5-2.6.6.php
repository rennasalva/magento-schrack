<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data SET `path` = 'schrack/typo3/terms_of_use_service_url', `value` = '';
    INSERT INTO core_config_data SET `path` = 'schrack/typo3/data_protection_service_url', `value` = '';
");

$installer->endSetup();
