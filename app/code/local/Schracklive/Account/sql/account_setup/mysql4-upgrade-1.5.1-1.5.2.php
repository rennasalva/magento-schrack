<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

/*
$installer->run("

    INSERT INTO core_config_data (config_id, scope, scope_id, path, value) VALUES(NULL, 'default', 0, 'schrack/account/message_queue_error', 'error');

");
*/

// do it manually

$installer->endSetup();
