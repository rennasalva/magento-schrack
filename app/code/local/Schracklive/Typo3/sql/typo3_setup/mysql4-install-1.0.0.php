<?php

$installer = $this;
$installer->startSetup();
$installer->run("
    INSERT INTO core_config_data (scope, scope_id, path, value) 
    SELECT scope, scope_id, REPLACE(path,'typo3menumain','notifyverionfilesupdate'), 
           REPLACE(value,'main','setResourceTimestamps') FROM core_config_data WHERE path = 'schrack/typo3/typo3menumain';
");

$installer->endSetup();
