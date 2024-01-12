<?php

$installer = $this;
$installer->startSetup();

$installer->run("    
    INSERT INTO core_config_data SET `path` = 'schrack/shop/log_offer_details', `value` = '';   
");

$installer->endSetup();
