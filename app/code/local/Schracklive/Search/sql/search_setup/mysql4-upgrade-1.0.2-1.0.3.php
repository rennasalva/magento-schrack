<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data SET `path` = 'schrack/solr/write_password', `value` = '';             
    INSERT INTO core_config_data SET `path` = 'schrack/solr/read_password', `value` = '';             
    ");

$installer->endSetup();
