<?php
$installer = $this;
$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data(path, value) VALUES ('schrack/solr/experiments', '') ON DUPLICATE KEY UPDATE path = 'schrack/solr/experiments';              
");

$installer->endSetup();
