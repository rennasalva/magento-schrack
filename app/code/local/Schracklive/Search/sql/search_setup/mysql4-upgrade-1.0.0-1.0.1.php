<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    DELETE FROM {$this->getTable('core_config_data')} WHERE `path` = 'schrack/solr/query_boost_accessories';
    ");

$installer->endSetup();
