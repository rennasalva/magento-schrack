<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data SET `path` = 'schrack/general/show_photovoltaic_text_in_checkout', `value` = '';   
");

$installer->endSetup();
