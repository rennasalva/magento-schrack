<?php

$installer = $this;
$installer->startSetup();

$url = Mage::getStoreConfig('schrack/s4s/user_record_update_url');
$url = str_replace('/updateuserprofile','/updatetermsofuse',$url);

$installer->run("
    INSERT INTO core_config_data (scope,scope_id,path,value) 
    VALUES('default',0,'schrack/s4s/terms_of_use_update_url','$url') 
    ON DUPLICATE KEY UPDATE value = value;
");
$installer->endSetup();
