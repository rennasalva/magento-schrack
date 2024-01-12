<?php

$installer = $this;
$installer->startSetup();
$installer->run("
    INSERT INTO core_config_data (scope,scope_id,path,value) 
    VALUES('default',0,'schrack/s4s/send_term_of_use_updates','0') 
    ON DUPLICATE KEY UPDATE value = value;
");
$installer->endSetup();
