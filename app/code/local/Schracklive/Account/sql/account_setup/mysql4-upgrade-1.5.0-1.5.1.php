<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();


if ( (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === '192.168.56.101') ) { // on dev machine
    $installer->run("
      INSERT INTO core_config_data (config_id, scope, scope_id, path, value) VALUES(NULL, 'default', 0, 'schrack/account/stomp_url', 'tcp://localhost:61613');
    ");
} else if (  Mage::getStoreConfig('schrackdev/development/test') == '1' ) { // on test machine
    $installer->run("
      INSERT INTO core_config_data (config_id, scope, scope_id, path, value) VALUES(NULL, 'default', 0, 'schrack/account/stomp_url', 'tcp://sevierd3.at.schrack.lan:61613');
    ");
}
// production values enter manually!


$installer->endSetup();
