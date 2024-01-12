<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
  INSERT INTO `wws_signal` (`code`, `wws_message`, `message`, `change_recreate`, `change_mail`, `change_mail_subject`, `change_mail_body`, `ship_recreate`, `ship_mail`, `ship_mail_subject`, `ship_mail_body`, `change_drop`, `ship_drop`) VALUES ('701', 'Ungueltige Angebotsversion [nr]', 'Outdated offer version', '0', '0', '', '', '0', '0', '', '', '0', '0');
");


$installer->endSetup();
