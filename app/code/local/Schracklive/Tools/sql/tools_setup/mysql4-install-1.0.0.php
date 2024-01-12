<?php

$installer = $this;

$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data (scope, scope_id, path, value) 
    VALUES('default', '0', 'schrack/customertools/pdf_generation_url', 'http://sl-ps1.schrack.com:8199/mq/buildfromxml');
");


$installer->endSetup();
