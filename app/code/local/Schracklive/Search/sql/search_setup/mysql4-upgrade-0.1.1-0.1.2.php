<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/query_boost_accessories', 'schrack_sts_is_accessory_boolS:false^100000000.0 OR schrack_sts_is_accessory_boolS:true^0');
    ");

$installer->endSetup();