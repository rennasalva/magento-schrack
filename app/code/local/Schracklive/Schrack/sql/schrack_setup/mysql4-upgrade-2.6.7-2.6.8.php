<?php

$installer = $this;
$installer->startSetup();


$installer->run("
    INSERT INTO core_config_data (scope,scope_id,path,value) 
    VALUES('default',0,'schrack/email/do_check_s4s_registration_email','0') 
    ON DUPLICATE KEY UPDATE value = value;
    INSERT INTO core_config_data (scope,scope_id,path,value) 
    VALUES('default',0,'schrack/email/do_check_SD_registration_email','0') 
    ON DUPLICATE KEY UPDATE value = value;
");

$installer->endSetup();
