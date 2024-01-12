<?php

$installer = $this;
$installer->startSetup();

$installer->run("    
    INSERT INTO core_config_data SET `path` = 'schrack/email/eyepin_check_url_active', `value` = '1';   
");

$installer->endSetup();
