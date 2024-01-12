<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data(path, value) VALUES ('schrack/customer/individualSKUs', 0) ON DUPLICATE KEY UPDATE path = 'schrack/customer/individualSKUs';              
    ");

$installer->endSetup();
