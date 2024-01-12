<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data SET `path` = 'schrack/solr/solrexport_synonyms_active', `value` = '1';
    INSERT INTO core_config_data(path, value) VALUES ('schrack/solr/solrexport_active', 1) ON DUPLICATE KEY UPDATE path = 'schrack/solr/solrexport_active';         
    ");

$installer->endSetup();
