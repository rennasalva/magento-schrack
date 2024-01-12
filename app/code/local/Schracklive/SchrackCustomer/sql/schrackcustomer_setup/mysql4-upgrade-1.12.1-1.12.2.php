<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data SET `path` = 'schrack/datanorm/priceinformation_pdf_url', `value` = '';    
");

$installer->endSetup();
