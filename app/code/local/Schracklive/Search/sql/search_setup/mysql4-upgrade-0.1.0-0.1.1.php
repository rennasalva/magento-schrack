<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    UPDATE {$this->getTable('core_config_data')} SET value = CONCAT('related_skus_textTM^30.0 ', value) WHERE path = 'schrack/solr/query_fields';
    ");

$installer->endSetup();