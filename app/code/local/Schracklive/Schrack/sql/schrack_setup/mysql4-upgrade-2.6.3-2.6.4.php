<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

    REPLACE INTO core_config_data (scope, scope_id, path, value)
    VALUES('default', '0', 'schrackdev/alertmails/recipients', 'j.wohlschlager@schrack.com,d.laslov@schrack.com');

");

$installer->endSetup();