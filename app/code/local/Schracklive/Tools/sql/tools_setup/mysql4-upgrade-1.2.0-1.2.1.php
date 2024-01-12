<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data SET `path` = 'schrack/general/addressvalidation_active', `value` = '1';    
    INSERT INTO core_config_data SET `path` = 'schrack/general/addressvalidation_url', `value` = 'https://api.address-validator.net/api/verify?';    
    INSERT INTO core_config_data SET `path` = 'schrack/general/addressvalidation_password', `value` = 'av-d37e4a19798e89fc279f32fd9eb1ac36';    
");

$installer->endSetup();


