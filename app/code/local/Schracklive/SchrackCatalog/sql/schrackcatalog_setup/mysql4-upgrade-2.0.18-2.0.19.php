<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->run("
    INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', '0', 'schrack/product_import/stomp_url', 'tcp://seviemq1.at.schrack.lan:61613');
    INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', '0', 'schrack/product_import/message_queue_inbound', 'sts_to_ws');
    INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', '0', 'schrack/product_import/error_queue', 'ws_error');
    INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', '0', 'schrack/product_import/process_messages_per_call_count', '1');
");

if ( strtoupper(Mage::getStoreConfig('schrack/general/country')) == 'AT' ) {
    $installer->run("
        INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', '0', 'schrack/product_import_dispatcher/contries_and_priorities', 'AT=1,BA=2,BE=2,BG=2,COM=3,CZ=2,DE=3,HR=2,HU=2,PL=2,RO=2,RS=2,RU=4,SA=4,SI=2,SK=2');
        INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', '0', 'schrack/product_import_dispatcher/running_processes', '4');
        INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', '0', 'schrack/product_import_dispatcher/commandline', 'setsid php %script% > /dev/null 2>&1 &');
    ");
}

$installer->endSetup();

