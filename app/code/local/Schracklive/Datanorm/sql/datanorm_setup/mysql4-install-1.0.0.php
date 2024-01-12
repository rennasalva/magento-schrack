<?php

/* @var Mage_Core_Model_Resource_Setup $installer */
//------------------------------------------------------------------ START SETUP
$installer = $this;  $installer->startSetup();
//------------------------------------------------------------------------------
$path = "SchrackServicePortal/SchrackCommonVersionedWebservice?wsdl";
//------------------------------------------------------------- Live Environment
$host = "https://dev.schrack.com/";
//------------------------------------------------------------- Test Environment
if (substr(gethostname(), 0, 2) == 'tl') {
    $host = "https://ws-test.schrack.com/";
}
if (substr(gethostname(), 0, 2) == 'sl') {
    $host = "https://ws.schrack.com/";
}
//---------------------------------------------------------------------- execute
$installer->run("
    UPDATE `core_config_data` SET `value` = '".$host.$path."' WHERE `path` = 'schrack/datanorm/wsdl';
");
//------------------------------------------------------------------------------
$installer->endSetup();

?>